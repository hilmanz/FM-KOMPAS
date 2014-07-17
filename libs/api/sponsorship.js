/**
api related to sponsorship
*/

var crypto = require('crypto');
var fs = require('fs');
var path = require('path');
var xmlparser = require('xml2json');
var async = require('async');
var config = require(path.resolve('./config')).config;
var mysql = require('mysql');
var dateFormat = require('dateformat');
var redis = require('redis');
var formations = require(path.resolve('./libs/game_config')).formations;
var sponsorship_chance = require(path.resolve('./libs/game_config')).sponsorship_chance;
var S = require('string');
var pool = null;

exports.setPool = function(p){
	pool = p;
}
function prepareDb(callback){

	pool.getConnection(function(err,conn){
		callback(conn);
	});
	
}
/** get the list of available sponsorships **/

function getAvailableSponsorship(game_team_id,callback){
	prepareDb(function(conn){
		conn.query("SELECT a.id,name,value,expiry_time,is_available \
				FROM ffgame.game_sponsorships a\
				WHERE a.is_available = 1 AND NOT EXISTS(\
					SELECT 1 FROM ffgame.game_team_sponsors b\
					WHERE b.game_team_id = ? AND b.sponsor_id = a.id LIMIT 1\
				) \
				LIMIT 100;",
				[game_team_id],
				function(err,rs){
					conn.release();
					callback(err,rs);	
					
				});
	});
	
}
function applySponsorship(game_id,matchday,game_team_id,sponsor_id,callback){
	
	var immediate_money = 0; //the immediate money recieved upon getting these sponsor.
	console.log(game_id,matchday,game_team_id,sponsor_id);
	//got the sponsorship, let's claim it.
	prepareDb(function(conn){
		async.waterfall([
			function(callback){
				console.log('detil sponsorship');
				//detil sponsorship
				conn.query("SELECT * FROM ffgame.game_sponsorships WHERE id=? LIMIT 1;",
							[sponsor_id],
							function(err,rs){
								try{
									callback(err,rs[0]);
								}catch(e){
									callback(new Error('no sponsorship found'),null);
								}
							});
			},
			function(sponsor_data,callback){
				// search immediate_money perk
				conn.query(
					"SELECT a.* FROM ffgame.game_sponsor_perks a\
					INNER JOIN ffgame.master_perks b \
					ON a.perk_id = b.id\
					WHERE a.sponsor_id=? AND b.perk_name = 'IMMEDIATE_MONEY';",
					[sponsor_id],
					function(err,rs){
						console.log(S(this.sql).collapseWhitespace().s);
						try{
							immediate_money = rs[0].amount;
							console.log('immediate money',immediate_money);
							callback(err,sponsor_data);
						}catch(e){
							callback(new Error('cannot query the perk'),sponsor_data);
						}
				});
			},
			function(sponsor_data,callback){
				conn.query("SELECT * FROM ffgame.game_team_sponsors\
							WHERE game_team_id=? LIMIT 1",
							[game_team_id],function(err,rs){
					
					console.log(S(this.sql).collapseWhitespace().s);
					
					if(err) console.log(err.message);
					
					callback(err,sponsor_data,rs[0]);
				});
			},
			function(sponsor_data,has_sponsor,callback){
				console.log(has_sponsor);
				if(!has_sponsor){
					conn.query("INSERT INTO ffgame.game_team_sponsors\
								(game_team_id,sponsor_id,valid_for)\
								VALUES(?,?,?)",
								[game_team_id,sponsor_id,38],
								function(err,result){
									console.log(S(this.sql).collapseWhitespace().s);
									callback(err,sponsor_data,result);
								});
				}else{
					console.log('udah punya sponsor nih');
					if(has_sponsor.sponsor_id == sponsor_id){
						callback(new Error('ALREADY_APPLIED'),null,null);
					}else{
						callback(new Error('ALREADY_HAVE_SPONSOR'),null,null);	
					}
					
				}
			},
			function(sponsor_data,insertResult,callback){
				if(insertResult){
					var item_name = 'Joining_Bonus';
					//add money if available
					if(immediate_money>0){
						conn.query("INSERT INTO ffgame.game_team_expenditures\
									(game_team_id,item_name,item_type,amount,game_id,\
									match_day,item_total,base_price)\
									VALUES\
									(?,?,1,?,?,?,1,1)\
									ON DUPLICATE KEY UPDATE\
									amount = VALUES(amount);",
									[game_team_id,item_name,immediate_money,game_id,matchday],
									function(err,rs){
										console.log(S(this.sql).collapseWhitespace().s);
										callback(err,rs);
									});
					}else{
						callback(null,true);	
					}
				}else{
					callback(null,true);	
				}
			}
		],
		function(err,result){
			
			conn.release();
			if(result){
				callback(err,true);
			}else{
				callback(err,false);
			}
			
			
		});
	});
	
}
function getActiveSponsors(game_team_id,callback){
	prepareDb(function(conn){
		conn.query("SELECT b.name,b.value,a.valid_for \
					FROM ffgame.game_team_sponsors a\
					INNER JOIN ffgame.game_sponsorships b\
					ON a.sponsor_id = b.id\
					WHERE a.game_team_id = ?;",
					[game_team_id],
					function(err,rs){
						conn.release();
						callback(err,rs);	
						
					});
	});
}
function roll(){
	var n = Math.random()*24;
	return Math.round(n);

}
exports.getAvailableSponsorship = getAvailableSponsorship;
exports.applySponsorship = applySponsorship;
exports.getActiveSponsors = getActiveSponsors;
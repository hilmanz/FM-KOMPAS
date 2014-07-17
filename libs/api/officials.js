/**
api related to officials
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
var pool = {};

exports.setPool = function(p){
	pool = p;
}
function prepareDb(callback){
	pool.getConnection(function(err,conn){
		callback(conn);
	});
}

/** get the list of officials **/
function official_list(game_team_id,done){
	prepareDb(function(conn){
		async.waterfall([
				function(callback){
					get_master_officials(conn,function(err,officials){
						callback(err,officials);
					});
				},
				function(officials,callback){
					get_user_officials(conn,game_team_id,officials,function(err,result){
						callback(err,result);
					});
				}
			],
			function(err,result){
				conn.release();
				done(err,result);						
				
			});
	});
}

function hire_official(game_team_id,official_id,callback){
	prepareDb(function(conn){
		async.waterfall(
			[
				function(callback){
					conn.query("INSERT IGNORE INTO ffgame.game_team_officials\
					(game_team_id,official_id,recruit_date)\
					VALUES\
					(?,?,NOW());",[game_team_id,official_id],function (err,rs){
						callback(err,rs.insertId);
					});		
				},
				function(staff_id,callback){
					conn.query("SELECT b.id,b.name FROM ffgame.game_team_officials a\
								INNER JOIN ffgame.game_officials b\
								ON a.official_id = b.id\
								WHERE a.id=?;",[staff_id],function(err,rs){
									console.log(rs);
									callback(err,rs[0]);
								});
				}
			],
			function(err,result){
				conn.release();
				callback(err,result);
				
			}
		);
	});
	
}
function remove_official(game_team_id,official_id,callback){
	prepareDb(function(conn){
		conn.query("DELETE FROM ffgame.game_team_officials WHERE game_team_id = ? AND official_id = ?",
					[game_team_id,official_id],function (err,rs){
						conn.release();
						callback(err,rs);
						
					});
	});
}

function get_master_officials(conn,callback){
	
	conn.query("SELECT * FROM ffgame.game_officials ORDER BY id LIMIT 20;",[],callback);

}
function get_user_officials(conn,game_team_id,officials,done){
	async.waterfall(
		[
			function(callback){
				conn.query("SELECT * FROM ffgame.game_team_officials WHERE game_team_id = ? LIMIT 20;",
							[game_team_id],
							function(err,rs){
								callback(err,rs);
							});
			},
			function(result,callback){
				for(var i in officials){
					for(var j in result){
						if(officials[i].id == result[j].official_id){
							officials[i].hired = true;
							break;
						}
					}
				}
				callback(null,officials);
			}
		],
		function(err,result){
			done(err,result);
		}
	);
}
exports.official_list = official_list;
exports.hire_official = hire_official;
exports.remove_official = remove_official;
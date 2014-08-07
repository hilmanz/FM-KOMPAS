/**
ranks and points updater
Multi-Worker.
**/
/////THE MODULES/////////
var config = require('./config').config;
var async = require('async');
var mysql = require('mysql');
var S = require('string');
/////DECLARATIONS/////////

var pool  = mysql.createPool({
		host     : config.database.host,
		user     : config.database.username,
		password : config.database.password,
	});

pool.getConnection(function(err, conn){
	async.waterfall([
		function(cb){
			getCurrentMatchday(conn, cb);
		},
		function(matchday, cb){
			getGameIdsByMatchday(conn, matchday, cb);
		},
		function(matchday, game_id, cb){
			var where_in = [];
			for(var i=0;i<game_id.length;i++){
				where_in.push(game_id[i].game_id);
			}
			
			cb(null, matchday, where_in, where_in.length);
		},
		function(matchday, where_in, length_game_id, cb){
			checkGameId(conn, matchday, where_in, length_game_id, cb);
			//console.log(where_in);
		},
		function(game_id, matchday, length_game_id, length, cb){
			if(length_game_id == length){
				compareResultJob(conn, game_id, matchday, cb);
			}
		},
		function(result, matchday, game_id, cb){
			if(result[0].total > 0 && result.length == 1){
				var start = 0;
				var limit = 1;
				var loop = true;
				async.whilst(
				    function () { return loop; },
				    function (callback) {
				        conn.query("SELECT league_id, team_id FROM \
				        			fantasy.league_member LIMIT ?,?", [start,limit],
						function(err, rs){
							console.log("getPlayer", rs);
							if(rs.length > 0){
								start += limit;
								async.each(rs, function(player, next){
									async.waterfall([
										function(cb){
											//console.log('player',player);
											getWeeklyPoint(conn, matchday, game_id, player, function(err){
												//cb(err, null);
											});
										}
									],
									function(err){
										next();
									});
								},
								function(err){
									done(err);
								});
						 		callback();
							}else{
								loop = false;
								callback();
							}
						});
				    },
				    function (err) {
				        console.log("Selesai");
				    }
				);
			}
		}
	], function(err){
		conn.release();
	});
});

function getCurrentMatchday(conn, cb){
	conn.query("SELECT matchday FROM \
				ffgame.game_fixtures \
				WHERE is_processed = 0 \
				ORDER BY id ASC LIMIT 1;",
				[],function(err, rs){
					console.log("getCurrentMatchday",rs);
					if(rs != null && rs.length == 1){
						cb(err,rs[0].matchday);
					}else{
						cb(new Error('no matchday found'),0);
					}
				});
}

function getGameIdsByMatchday(conn, matchday, cb){
	conn.query("SELECT game_id,period FROM \
				ffgame.game_fixtures \
				WHERE matchday = ? \
				ORDER BY id ASC LIMIT 40;",
				[matchday],function(err, rs){
					console.log("getGameIdsByMatchday",rs);
					if(rs != null && rs.length > 0){
						cb(err, matchday, rs);
					}else{
						cb(new Error('no matchday found'), matchday, []);
					}
				});
}

function checkGameId(conn, matchday, game_id, length_game_id, cb){
	conn.query("SELECT id FROM ffgame_stats.job_queue WHERE game_id IN (?) GROUP BY game_id",
				[game_id], function(err, rs){
					console.log("checkGameId", rs);
					//console.log('GAME_TEAM_POINTS',S(this.sql).collapseWhitespace().s);
					cb(err, game_id, matchday, length_game_id, rs.length);
				});
}

function compareResultJob(conn, game_id, matchday, cb){
	var where_in = [];
	for(var i=0;i<game_id.length;i++){
		where_in.push("'"+game_id[i]+"'");
	}

	var sql = "(SELECT \
				    COUNT(id) as total \
				FROM \
				    ffgame_stats.job_queue \
				WHERE \
				    game_id IN ("+where_in+") \
				        AND n_status = 2) \
				UNION \
				(SELECT \
				    COUNT(id) as total \
				FROM \
				    ffgame_stats.job_queue_rank \
				WHERE \
				    game_id IN ("+where_in+") \
				        AND n_status = 2)";
	conn.query(sql,[],
				function(err, rs){
					console.log("compareResultJob", rs);
			 		cb(err, rs, matchday, game_id);
				});
}
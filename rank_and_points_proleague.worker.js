/**
ranks and points updater
Multi-Worker.
**/
/////THE MODULES/////////
var fs = require('fs');
var path = require('path');
var config = require('./config').config;
var xmlparser = require('xml2json');
var master = require('./libs/master');
var async = require('async');
var mysql = require('mysql');
var util = require('util');
var argv = require('optimist').argv;
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
				getPlayer(conn, matchday, game_id, cb);
			}
		},
		function(result_player, matchday, game_id, done){
			async.each(result_player, function(player, next){
				async.waterfall([
					function(cb){
						console.log('player',player);
						getWeeklyPoint(conn, matchday, game_id, player, function(err){
							cb(err, null);
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
			 		cb(err, rs, matchday, game_id);
				});
}

function getPlayer(conn, matchday, game_id, cb){
	conn.query("SELECT league_id, team_id FROM fantasy.league_member LIMIT 100000",[],
				function(err, rs){
			 		cb(err, rs, matchday, game_id);

				});
}

function getWeeklyPoint(conn, matchday, game_id, player, cb){
	conn.query("SELECT * FROM fantasy.weekly_points \
				WHERE game_id IN(?) AND team_id=? AND matchday = ? LIMIT 10000", 
				[game_id, player.team_id, matchday],
				function(err, rs){
					if(rs!=null && rs.length > 0){
						async.each(rs, function(value, next){
							conn.query("INSERT IGNORE INTO fantasy.league_table(league_id, team_id, game_id, matchday, matchdate, points) VALUES(?, ?, ?, ?, ?, ?)
									",[player.league_id, player.team_id, rs.game_id,
									 rs.matchday, rs.matchdate, 
									 rs.points+rs.extra_points],
									function(err, rs){
										cb(err);
									});
						},
						function(err){
							cb(err);
						});
						/**/
					}
				});
}

function insertLeagueTable(conn, player, weekly_points, cb){
	//for(var i=0;i<weekly_points.length;i++){
		//console.log(weekly_points[i].game_id);
		/*conn.query("INSERT INTO fantasy.league_table(league_id, team_id, game_id, matchday, matchdate, points) VALUES(?, ?, ?, ?, ?, ?)
					",[player.league_id, player.team_id, weekly_points[i].game_id,
					 weekly_points[i].matchday, weekly_points[i].matchdate, 
					 weekly_points[i].points+weekly_points[i].extra_points],
					function(err, rs){

						cb(err);
					});*/
	//}

	/*conn.query("SELECT 1+1",[], function(err, rs){
		console.log(rs);
	});*/
	console.log("Foo");
	
}
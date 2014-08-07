/**
* module for updating the match results across all users's lineups
*
* we only calculate unprocessed team.
* the processed team will have an entry in game_team_lineups_history.
* if there's no existing game_id on the history, we will do the calculation.
* 
* here's the rule
* 1. we only update the lineup history for team which playes in the game (game_id)
* 2. for those team which not played in the game, we only track for the player who played in the real-world.
*    and add the stats to the team.
*/

/**
* the module to read match_results file.
*/
var fs = require('fs');
var path = require('path');
var async = require('async');
var xmlparser = require('xml2json');
var config = require(path.resolve('./config')).config;
var S = require('string');
var mysql = require('mysql');
var pool  = mysql.createPool({
   host     : config.database.host,
   user     : config.database.username,
   password : config.database.password,
});
var punishment = require(path.resolve('./libs/gamestats/punishment_rules'));
var player_stats_category = require(path.resolve('./libs/game_config')).player_stats_category;

console.log('lineup_stats - creating pool');
exports.update = function(game_id,start,done){
	//the updates will run for each 100 entries.
	
	var limit = 100;
	var is_complete = false;
	
	async.waterfall(
		[
			function(callback){
				get_user_teams(start,limit,function(err,team){
					//if there's no more data. we stop :)
					console.log(team);
					if(team.length<limit){
						is_complete = true;
						console.log("NO MORE TEAM TO PROCESS");
					}else{
						start+=limit;
					}
					callback(err,team);
				});	
			},
			function(team,callback){
				get_master_team_stats(game_id,function(err,player_stats){
					callback(err,game_id,team,player_stats);
				});
			},
			function(game_id,team,player_stats,callback){
				get_master_match_summary(game_id,function(err,team_summary){
					callback(err,game_id,team,player_stats,team_summary);
				})
			},
			function(game_id,team,player_stats,team_summary,callback){
				update_team_stats(game_id,team,player_stats,team_summary,function(err){
					callback(err,'ok');
				});
			},
			function(result,callback){
				update_team_points(function(err){
					callback(err,'ok');
				})
			}
		],
		function(err,result){
			
			done(err,is_complete,start);
		}
	);	
		
	
	
}
exports.done = function(){
	pool.end(function(err){
		if(err) console.log('match_results','error',err.message);
		console.log('lineup_stats','pool closed');
	});
}

function get_master_team_stats(game_id,done){
	pool.getConnection(function(err,conn){
		conn.query("SELECT * FROM "+config.database.statsdb+".master_match_player_points\
					WHERE game_id = ? LIMIT 50",
					[game_id],
		function(err,rs){
			if(err){console.log('lineupstats - ERROR - ',err.message);}
			conn.end(function(err){
				done(err,rs);	
			});
		});

	});
}
function get_master_match_summary(game_id,done){
	pool.getConnection(function(err,conn){
		conn.query("SELECT * FROM "+config.database.statsdb+".master_match_points\
					WHERE game_id = ? LIMIT 30",
					[game_id],
		function(err,rs){
			if(err){console.log(err.message);}
			conn.end(function(err){
				for(var i in rs){
					rs[i].avg_points = rs[i].overall_points / 11;
				}
				done(err,rs);	
			});
		});

	});
}
function get_user_teams(start,limit,done){
	pool.getConnection(function(err,conn){
		if(err) console.log(err.message);
		conn.query("SELECT * FROM "+config.database.database+".game_teams ORDER BY id ASC LIMIT ?,?",
				[start,limit],
		function(err,rs){
			if(err){console.log(err.message);}
			conn.end(function(err){
				done(err,rs);	
			});
		});
	});
}
function get_user_teams_by_idRange(since_id,until_id,limit,done){
	pool.getConnection(function(err,conn){
		if(err) console.log(err.message);
		conn.query("SELECT * FROM "+config.database.database+".game_teams WHERE id BETWEEN ? AND ? ORDER BY id ASC LIMIT ?,?",
				[since_id,until_id,start,limit],
		function(err,rs){
			if(err){console.log(err.message);}
			conn.end(function(err){
				done(err,rs);	
			});
		});
	});
}
function update_team_stats(game_id,team,player_stats,team_summary,done){
	//console.log(team,player_stats,team_summary);
	console.log('team_summary',team_summary);
	var summary = {}; //team summary, this will be used for player's performance change modifier.
	for(var i in team_summary){
		summary[team_summary[i].team_id] = {points:team_summary[i].overall_points,
											average:team_summary[i].avg_points};

	}
	console.log(summary);
	pool.getConnection(function(err,conn){
		async.eachSeries(team,
						function(item,callback){
							async.waterfall([
									function(next){
										update_individual_team_stats(game_id,item,summary,player_stats,
											function(err){
											next(err);	
										});
									},
									function(next){
										console.log('ISSUE1','check penalty if the club rooster is unbalance');
										punishment.check_violation(conn,game_id,item.id,item.team_id,
											function(err,rs){
												next(err);
										});
									},
									function(next){
										console.log('ISSUE1','Lets process the extra points if the week has ended....')
										//check the game's matchday
										conn.query("SELECT matchday \
													FROM "+config.database.database+".game_fixtures \
													WHERE game_id=? \
													LIMIT 1",[game_id],
													function(err,r){
														//console.log('-----',this.sql,'---');
														//console.log(r);
														var matchday = 0;
														if(!err){
															matchday = r[0].matchday;
														}
														next(err,matchday);
													});
										
									},
									function(matchday,next){
										async.waterfall([
												function(cb){
													console.log('ISSUE1','checking for #',team);
													conn.query("SELECT game_id FROM "+config.database.database+".game_fixtures \
															WHERE (home_id = ? OR away_id = ?) \
															AND matchday=? LIMIT 1",
															[item.team_id,item.team_id,matchday],
															function(err,match){
																//console.log(S(this.sql).collapseWhitespace().s);
																//console.log('ISSUE1','1# get the game_id : ',this.sql);
																var the_game_id = '';
																try{
																	the_game_id = match[0]['game_id'];
																}catch(e){
																	err = new Error('no game_id found');
																}
																//console.log('the game id : ',the_game_id);

																cb(err,the_game_id);
															});
												},
												function(t_game_id,cb){
													conn.query("SELECT a.player_id,a.position_no,b.position \
																FROM "+config.database.database+".game_team_lineups_history a\
																INNER JOIN "+config.database.database+".master_player b\
																ON a.player_id = b.uid\
																WHERE a.game_id = ?\
																AND a.game_team_id=? LIMIT 16;",
																[t_game_id,item.id],
																function(err,rs){
																	//console.log('ISSUE1',S(this.sql).collapseWhitespace().s);
																	cb(err,rs);
																});
												},
												function(lineup_players,cb){
													//console.log('ISSUE1',lineup_players);
													async.eachSeries(lineup_players,function(lp,nx){
														var is_sub = false;
														if(lp.position_no>11){
															is_sub = true;
														}
														getPlayerDailyTeamStats(conn,
																			item.id,
																			lp.player_id,
																			lp.position,
																			matchday,
																			lp.position_no,
																			function(err,rs){
																nx();
														});
													},function(err){
														cb(err,matchday);
													});
													
												}
											],
											function(err,rs){
												next(err,matchday);
										});
									},
									function(matchday,next){
										console.log('check if the week has lasted');
										//check if the week has lasted.
										conn.query("SELECT COUNT(*) AS total \
													FROM "+config.database.database+".game_fixtures \
													WHERE period = 'FullTime' \
													AND matchday = ? \
													AND is_processed=1",[matchday],
													function(err,r){
														//console.log('-----',this.sql,'---');
														var is_finished = false;
														if(!err){
															if(r[0].total==10){
																is_finished = true;
															}
														}
														next(err,matchday,is_finished);
													});
									},

									function(matchday,is_finished,next){
										console.log('matchday : ',matchday,'is finished : ',is_finished);
										console.log('is all player started ?');
										var is_all_player_started = false;

										if(is_finished){
											async.waterfall([
													function(cb){
														conn.query("SELECT game_id \
																	FROM "+config.database.database+".game_fixtures \
																	WHERE (home_id =? OR away_id=?) \
																	AND matchday = ?;",
																	[item.team_id,item.team_id,matchday],
																	function(err,r){	
																		//console.log('--> we need the exact game_id',
																		//			this.sql);
																		var the_game_id = '';
																		if(!err){
																			try{
																				the_game_id = r[0].game_id;
																			}catch(e){
																				the_game_id = '';
																			}
																			
																		}
																		cb(err,the_game_id);
																	});
													}
												],
												function(err,the_game_id){
													//check if all lineup is played in real game.
													conn.query("SELECT * FROM \
														"+config.database.database+".game_team_lineups_history a\
														INNER JOIN\
														"+config.database.statsdb+".master_player_stats b\
														ON a.player_id = b.player_id\
														INNER JOIN "+config.database.database+".game_fixtures c\
														ON b.game_id = c.game_id\
														WHERE a.game_team_id=? AND a.game_id = ?\
														AND b.stats_name = 'game_started'\
														AND c.matchday = ?\
														AND a.position_no < 12;",[
															item.id,the_game_id,matchday
														],
														function(err,r){
															//console.log('-----',this.sql,'---');
															if(!err){
																if(r.length==11){
																	is_all_player_started = true;
																}
															}
															next(err,the_game_id,matchday,is_finished,is_all_player_started);

													});
												}
											);
											
										}else{
											next(err,'',matchday,is_finished,is_all_player_started);
										}
									},

									function(the_game_id,matchday,is_finished,is_all_player_started,next){
										console.log('is budget below zero ?');
										var is_team_budget_below_zero = false;
										if(is_finished){
											conn.query("SELECT SUM(budget+expenses) AS balance\
														FROM (\
															SELECT budget,0 AS expenses \
															FROM "+config.database.database+".game_team_purse \
															WHERE game_team_id=?\
														UNION ALL\
															SELECT 0,SUM(amount) AS total \
															FROM "+config.database.database+".game_team_expenditures \
															WHERE match_day <= ? AND game_team_id=?\
														) a;",
												[item.id,matchday,item.id],
												function(err,r){
												console.log('-----',this.sql,'---');
												var balance = 0;
												if(!err){
													balance = r[0].balance;
													if(r[0].balance < 0){
														is_team_budget_below_zero = true;
													}
												}
												next(err,
													the_game_id,
													matchday,
													is_finished,is_all_player_started,
													is_team_budget_below_zero,
													balance);
											});
										}else{
											next(err,
													the_game_id,
													matchday,
													is_finished,is_all_player_started,
													is_team_budget_below_zero,
													0);
										}
									},
									function(the_game_id,matchday,is_finished,is_all_player_started,
											is_team_budget_below_zero,balance,next){
										console.log('wrapping up buddy !');
										if(is_finished){
											async.waterfall([
												function(cb){
													if(is_all_player_started){
														conn.query("INSERT INTO \
																	"+config.database.statsdb+".game_team_extra_points\
																	(game_id,matchday,game_team_id,\
																		modifier_name,extra_points)\
																	VALUES\
																	(?,?,?,?,?)\
																	ON DUPLICATE KEY UPDATE\
																	extra_points = VALUES(extra_points);",
																	[
																		the_game_id,
																		matchday,
																		item.id,
																		'ALL_LINEUP_IS_PLAYED_BONUS',
																		20
																	],function(err,r){
																		//console.log('-----',this.sql,'---');
																		cb(err);
																	});
													}else{
														cb(err);
													}
												},
												function(cb){
													if(is_team_budget_below_zero){
														var penalty = Math.floor(balance/100000) * 100;
														console.log('PENALTY : ',penalty);
														conn.query("INSERT INTO \
																	"+config.database.statsdb+".game_team_extra_points\
																	(game_id,matchday,game_team_id,\
																		modifier_name,extra_points)\
																	VALUES\
																	(?,?,?,?,?)\
																	ON DUPLICATE KEY UPDATE\
																	extra_points = VALUES(extra_points);",
																	[
																		the_game_id,
																		matchday,
																		item.id,
																		'BALANCE_IS_BELOW_ZERO',
																		penalty
																	],function(err,r){
																		//console.log('-----',this.sql,'---');
																		cb(err,true);
																	});
													}else{
														cb(err,true);
													}
												},
												function(all_ok,cb){
													//send notification that the player has recieved the extra points
													if(is_all_player_started){
														msg = "Selamat, anda baru saja mendapatkan bonus poin sebesar 20 dipertandingan lalu.";
														conn.query("INSERT INTO "+config.database.frontend_schema+".notifications\
																	(content,url,dt,game_team_id)\
																	VALUES\
																	(?,'#',NOW(),?)",[msg,item.id],function(err,rs){
																		//console.log('---',this.sql,'----');
																		cb(err,true);
															});
													}else{
														cb(null,true);
													}
												},
												function(all_ok,cb){
													//send notification that the player has recieved the extra points
													if(is_team_budget_below_zero){
														var penalty = Math.floor(balance/100000) * 100;
														msg = "Kamu mendapatkan potongan poin sebesar "+penalty+" karena keuangan kamu negatif";
														conn.query("INSERT INTO "+config.database.frontend_schema+".notifications\
																	(content,url,dt,game_team_id)\
																	VALUES\
																	(?,'#',NOW(),?)",[msg,item.id],function(err,rs){
																		//console.log('---',this.sql,'----');
																		cb(err,true);
															});
													}else{
														cb(null,true);
													}
												}

											],function(err,endOfProcess){
												next(err,endOfProcess);
											});
										}else{
											next(err,true);
										}
									}
								],
								function(err,wf_result){
									callback();
								});
							
						},
			function(err){
				conn.end(function(err){
					done(null);
					console.log('done')
				});
			}
		);

		
	});
}

function getPlayerDailyTeamStats(conn,game_team_id,player_id,player_pos,matchday,position_no,done){
	console.log('ISSUE2','updating matchday#',matchday,'stats for player #',player_id,'in team #',game_team_id);
	var pos = 'g';
	switch(player_pos){
		case 'Forward':
			pos = 'f';
		break;
		case 'Midfielder':
			pos = 'm';
		break;
		case 'Defender':
			pos = 'd';
		break;
		default:
			pos = 'g';
		break;
	}
	
	sql = "SELECT a.game_id,stats_name,stats_value\
			FROM "+config.database.statsdb+".master_player_stats a \
			INNER JOIN "+config.database.database+".game_fixtures b\
			ON a.game_id = b.game_id\
			WHERE a.player_id=? AND b.matchday=?\
			AND EXISTS(\
				SELECT 1\
				FROM "+config.database.database+".game_team_players c\
				WHERE c.game_team_id=?\
				AND c.player_id = a.player_id\
				LIMIT 1\
			)\
			AND EXISTS(\
				SELECT 1 FROM "+config.database.statsdb+".game_match_player_points d\
				WHERE d.game_team_id=? AND d.game_id = a.game_id \
				AND d.player_id = a.player_id LIMIT 1\
			)\
			LIMIT 500;";
	
	
	async.waterfall([
		function(callback){
			conn.query(sql,
				[player_id,matchday,game_team_id,game_team_id],
				function(err,rs){
					//console.log(S(this.sql).collapseWhitespace().s);		
					callback(err,rs);	
					
				});
		},
		function(result,callback){
			conn.query("SELECT * FROM "+config.database.database+".game_matchstats_modifier;",
			[],
			function(err,rs){
				callback(err,rs,result);	
				
			});
		},
		function(modifiers,result,callback){
			//mapping
			var weekly = [];
			var point_modifier = 1.0;
			if(position_no > 11){
				//if substitution, all points reduced to 50%
				point_modifier = 0.5;
			}
			if(result.length>0){
				for(var i in result){
					
					//distributed the counts for each categories
					for(var category in player_stats_category){
						for(var j in player_stats_category[category]){
							if(player_stats_category[category][j] == result[i].stats_name){
								var points =  (parseInt(result[i].stats_value) * getModifierValue(modifiers,
																	  					result[i].stats_name,
																	  					pos));
								weekly.push({
									game_id:result[i].game_id,
									category:category,
									game_team_id:game_team_id,
									player_id:player_id,
									matchday:matchday,
									stats_name:result[i].stats_name,
									stats_value:result[i].stats_value,
									points: (points * point_modifier),
									position_no: position_no

								});
							}
						}
					}
				}
			}
			//console.log(weekly);
			callback(null,weekly);
		},
		function(weekly,callback){

			async.eachSeries(weekly,function(w,next){
				conn.query("INSERT INTO "+config.database.statsdb+".game_team_player_weekly\
						(game_id,game_team_id,matchday,player_id,stats_category,stats_name,stats_value,points,position_no)\
						VALUES\
						(?,?,?,?,?,?,?,?,?)\
						ON DUPLICATE KEY UPDATE\
						stats_value = VALUES(stats_value),\
						points = VALUES(points),\
						position_no = VALUES(position_no)",
						[w.game_id,
						w.game_team_id,
						 w.matchday,
						 w.player_id,
						 w.category,
						 w.stats_name,
						 w.stats_value,
						 w.points,
						 w.position_no
						 ],function(err,rs){
						 	//console.log(S(this.sql).collapseWhitespace().s);
						 	next();
						});
			},
			function(err){callback(err,weekly)});
			
		}
	],
	function(err,result){
		done(err,result);
	});
}
/**
* get modifier value based on player's position
*/
function getModifierValue(modifiers,stats_name,position){
	
	for(var i in modifiers){
		if(modifiers[i].name==stats_name){
			return (parseInt(modifiers[i][position]));
		}
	}
	return 0;
}


//update user's team stats individually. (currently we need to process each of 35000++ users)
function update_individual_team_stats(game_id,team,summary,player_stats,done){
		async.waterfall(
			[
				function(callback){
					console.log('UPDATE_INDIVIDUAL',team,'vs',summary);
					//if(typeof summary[team.team_id]!=='undefined'){
						console.log('HISTORY','track the lineup history for #',team.id);

						//@TODO : ini nanti kayaknya harus dieksekusi sebelum step 2.
						//agar jika kita sedang me regen data,  lineup yg dipakai adalah lineup dari history.
						//bukan lineup actual.
						addToHistory(game_id,team,function(err){
							callback(err);	
						});
					//}else{
						//console.log('no need to track the lineup history for #',team.team_id);
						//callback(null);	
					//}
				},
				function(callback){
					//step 1 - get team lineups
					//@TODO perlu dipikirkan apakah kita perlu narik lineup dari lineup history ?

					getTeamLineups(game_id,team,function(err,lineups){
						callback(null,lineups);
					});
					
				},
				function(lineups,callback){
					var in_game = true;
					if(typeof summary[team.team_id] === 'undefined'){
						in_game = false;
					}
					//step 2 - update lineup stats
					updateLineupStats(game_id,lineups,summary,player_stats,in_game,function(err,rs){
						callback(err,rs);
					});
					
				}
				
			],
				function(err,result){
					done(err);
				}
			);
	
	
}
function addToHistory(game_id,team,done){
	var matchday = 0;
	pool.getConnection(function(err,conn){
		async.waterfall([
			function(callback){
				conn.query("SELECT matchday FROM "+config.database.database+".game_fixtures WHERE game_id=? LIMIT 1",
							[game_id],function(err,match){
								console.log('ISSUE1',this.sql);
								try{
									matchday = match[0]['matchday'];
								}catch(e){
									matchday = 0;	
								}
								callback(err);
							});
			},
			function(callback){
				conn.query("SELECT game_id FROM "+config.database.database+".game_fixtures \
							WHERE (home_id = ? OR away_id = ?) \
							AND matchday=? LIMIT 1",
							[team.team_id,team.team_id,matchday],function(err,match){
								console.log('ISSUE1','0# get the game_id : ',this.sql);
								var the_game_id = '';
								try{
									the_game_id = match[0]['game_id'];
								}catch(e){
									err = new Error('no game_id found');
								}
								console.log('the game id : ',the_game_id);

								callback(err,the_game_id);
							});
			},
			function(the_game_id,callback){
				var can_insert = true;
				//we only do insert once
				conn.query("SELECT * FROM "+config.database.database+".game_team_lineups_history\
							WHERE game_id = ? AND game_team_id= ?",
							[the_game_id,team.id],function(err,rs){
								console.log('ISSUE1','check if theres already history',S(this.sql).collapseWhitespace().s);
								try{
									if(rs.length>0){
										can_insert = false;
									}
								}catch(e){
									can_insert = false;
								}
								callback(err,the_game_id,can_insert);
							});
			},
			function(the_game_id,can_insert,callback){
				if(can_insert){
					conn.query("INSERT IGNORE INTO \
					"+config.database.database+".game_team_lineups_history\
					(game_id,game_team_id,player_id,position_no,last_update)\
					SELECT ? AS game_id,game_team_id,player_id,position_no,NOW() AS last_update\
					FROM "+config.database.database+".game_team_lineups WHERE game_team_id=?;",
					[the_game_id,team.id],
					function(err,rs){
						console.log('ISSUE1','update lineup history ',S(this.sql).collapseWhitespace().s);
						callback(err,rs);
					});
				}else{
					console.log('ISSUE1','no insert history #',team.id,' - ',the_game_id);
					callback(null,null);
				}
				
			}
		],function(err,rs){
			conn.end(function(err){
				done(err);
			});
		});
	});
}
function getTeamLineups(game_id,team,done){
	var matchday = 0;
	pool.getConnection(function(err,conn){
		async.waterfall([
			function(callback){
				conn.query("SELECT matchday FROM "+config.database.database+".game_fixtures WHERE game_id=? LIMIT 1",
							[game_id],function(err,match){
								console.log('ISSUE1',this.sql);
								try{
									matchday = match[0]['matchday'];
								}catch(e){
									matchday = 0;	
								}
								callback(err);
							});
			},
			function(callback){
				conn.query("SELECT game_id FROM "+config.database.database+".game_fixtures \
							WHERE (home_id = ? OR away_id = ?) \
							AND matchday=? LIMIT 1",
							[team.team_id,team.team_id,matchday],function(err,match){
								console.log('ISSUE1','get the game_id : ',this.sql);
								var the_game_id = '';
								try{
									the_game_id = match[0]['game_id'];
								}catch(e){
									err = new Error('no game_id found');
								}
								console.log('the game id : ',the_game_id);

								callback(err,the_game_id);
							});
			},
			function(the_game_id,callback){
				conn.query("SELECT * FROM "+config.database.database+".game_team_lineups_history \
					WHERE game_id=? AND game_team_id = ?\
					LIMIT 20",
					[the_game_id,team.id],function(err,rs){
							console.log('ISSUE1','lineup : ',this.sql);
							callback(err,rs);
					});
			}
		],
		function(err,rs){
			conn.end(function(err){
					console.log('ISSUE1','get team lineup ends');
					if(typeof rs !== 'object' && rs.length > 0){
						err = new Error('no lineups :(');
						rs = [];
					}
					done(err,rs);
			});
		});
		
	});	
}
function updateLineupStats(game_id,lineups,summary,player_stats,in_game,done){
	console.log('player stats : ',player_stats);
	pool.getConnection(function(err,conn){
		async.eachSeries(lineups,
						function(item,callback){
							//console.log(item);
							var stats = {player_id:item.player_id,
										 points: 0,
										 performance: 0};
							var is_found = false;
							async.waterfall([
									function(callback){
										conn.query("SELECT game_id,player_id,stats_name \
													FROM "+config.database.statsdb+".master_player_stats \
													WHERE game_id = ?  AND player_id= ? \
													AND stats_name = 'game_started' LIMIT 1;",
													[game_id,item.player_id],function(err,sqlResult){
														
														var isStarter = false;
														
														sqlResult = sqlResult || [];
														if(sqlResult.length>0){
															isStarter = true;
														}
														console.log('check starter status',game_id,item.player_id,isStarter);
														callback(err,isStarter);
													});
									},
									function(isStarter,callback){
										for(var i in player_stats){
											if(item.player_id==player_stats[i].player_id){
												stats.points = player_stats[i].points;
												//if(!isStarter){
												//	console.log('points reduced to 75%',(stats.points*0.75));
												//	stats.points = stats.points * 0.75;
												//}
												var apts =  summary[player_stats[i].team_id].average;
												stats.performance = ((stats.points - apts)/apts)*100;
												is_found = true;
												break;
											}
										}
										if(!is_found && !in_game){
											//we skip it if the player is not matched one of the player_stats,
											//and the team is not involve with the game at all.
											console.log('skip #',item.player_id,' from team #',item.game_team_id);
											callback(err,null);
										}else{
											console.log('add #',item.player_id,' from team #',item.game_team_id,' stats');
											conn.query("INSERT INTO "+config.database.statsdb+".game_match_player_points\
												(game_id,game_team_id,player_id,points,performance,last_update)\
												VALUES(?,?,?,?,?,NOW())\
												ON DUPLICATE KEY UPDATE\
												points = VALUES(points),\
												performance = VALUES(performance);",
												[	game_id,
													item.game_team_id,
													stats.player_id,
													stats.points,
													stats.performance ],
												function(err,rs){
													console.log(this.sql);
													callback(err,rs);	
											});
										}
									}

								],
								function(err,rsWaterfall){
									callback();
								}
							);
							
							
						},
						function(err){
							conn.end(function(err){
								done(null,[]);	
							});
						});
	});
}

/**
* updating the team's overall points
*/
function update_team_points(done){
	console.log('updating team points');
	pool.getConnection(function(err,conn){
		conn.query("INSERT INTO "+config.database.statsdb+".game_team_points\
					(game_team_id,points)\
					SELECT game_team_id,SUM(points) AS total_points\
					FROM "+config.database.statsdb+".game_team_player_weekly\
					GROUP BY game_team_id\
					ON DUPLICATE KEY UPDATE\
					points = VALUES(points);",
					[],function(err,rs){
						conn.end(function(err){
							done(err);	
						});
					});
	});
}
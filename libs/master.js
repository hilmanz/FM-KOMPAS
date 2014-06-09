/**
* master.js
* this is a model which handles all master data.
*
*/
var mysql = require('mysql');
var config = require('../config').config;
var S = require('string');
var pool  = mysql.createPool({
   host     : config.database.host,
			  user     : config.database.username,
			  password : config.database.password,
});
exports.update_team_data = function(data,callback){
		var conn = mysql.createConnection(
			{
	   			host: config.database.host,
			  	user: config.database.username,
			  	password: config.database.password,
			}
		);

		var team_data = [];
		for(var i in data.SoccerFeed.SoccerDocument.Team){
				var team = data.SoccerFeed.SoccerDocument.Team[i];
				console.log(team);

				//insert / update the team sequentially.. it's slower but better.
				var sql = "INSERT INTO ffgame.master_team\
						   (uid,name,founded,symid,stadium_id,stadium_name,stadium_capacity)\
						    VALUES\
						   (?,?,?,?,?,?,?) \
						   ON DUPLICATE KEY UPDATE \
						   name = VALUES(name),\
						   founded = VALUES(founded),\
						   symid = VALUES(symid),\
						   stadium_id = VALUES(stadium_id),\
						   stadium_name = VALUES(stadium_name),\
						   stadium_capacity = VALUES(stadium_capacity);";
				try{


					var query = conn.query(sql,[team.uID,team.Name,team.Founded,
									team.SYMID,'',team.Stadium.Name,
									team.Stadium.Capacity],
									function(err,result){
										if(err) console.log(err.message);
										//console.log(result);
									});
					(function(){
						var t = team;
						var q = conn.query("SELECT uid FROM ffgame.master_team WHERE symid=? LIMIT 1;",
									[t.SYMID]);
						q.on('result',function(row){
							team_data.push({team_id: row.uid,
											players: t.Player});
						});

					}());
				}catch(e){
					//do nothing
				}
				

		}
	function onTeamInserted(err,result){
		if(err) console.log(err.message);
		console.log(result);
	}
	
	conn.release();
		console.log('db complete');
		update_player_data(team_data,callback);
	

	function update_player_data(team_data,callback){
		console.log(team_data);
		var conn = mysql.createConnection(
			{
	   			host: config.database.host,
			  	user: config.database.username,
			  	password: config.database.password,
			}
		);
		for(var i in team_data){
			var players = team_data[i].players;
			console.log(team_data[i].team_id,'->',players);
			var team_id = team_data[i].team_id;
			for(var j in players){
				var player = players[j];
				var stat = {};
				for(var s in player.Stat){
					stat[player.Stat[s].Type] = player.Stat[s]['$t'];
				}
				var sql = "INSERT INTO ffgame.master_player\
						 (uid,name,position,first_name,last_name,known_name,birth_date,\
						 	weight,height,jersey_num,real_position,real_position_side,\
						 	join_date,country,team_id)\
						 VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)\
						 ON DUPLICATE KEY UPDATE\
						 name= VALUES(name),\
						 position= VALUES(position),\
						 first_name= VALUES(first_name),\
						 last_name= VALUES(last_name),\
						 known_name= VALUES(known_name),\
						 birth_date= VALUES(birth_date),\
						 weight= VALUES(weight),\
						 height= VALUES(height),\
						 jersey_num= VALUES(jersey_num),\
						 real_position= VALUES(real_position),\
						 real_position_side= VALUES(real_position_side),\
						 join_date= VALUES(join_date),\
						 country= VALUES(country),\
						 team_id= VALUES(team_id)\
						 ;";
				
				var params = [
					player.uID,
					S(player.Name).decodeHTMLEntities().s,
					player.Position,
					S(stat.first_name).decodeHTMLEntities().s,
					S(stat.last_name).decodeHTMLEntities().s,
					stat.known_name,
					stat.birth_date,
					stat.weight,
					stat.height,
					stat.jersey_num,
					stat.real_position,
					stat.real_position_side,
					stat.join_date,
					stat.country,
					team_id
				];
				var q1 = conn.query(sql,params,function(err,rs){
					if(err) console.log(err.message);
					console.log(this.sql);
				});
			}
		}
		conn.release();
		console.log('adding players completed !');
		callback(team_data);
		
		
	}
	
}

exports.update_team_player_data = function(data,callback){
	for(var i in data.SoccerFeed.SoccerDocument.Team){
		var team = data.SoccerFeed.SoccerDocument.Team[i];
		for(var j in team.Player){
			var player = team.Player[j];
			console.log(player);
		}
	}
}
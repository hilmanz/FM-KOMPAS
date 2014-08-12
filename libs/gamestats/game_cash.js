var S = require('string');
/**
* module for automatically add in-game cash based on a fraction of latest points. 
* (currently we set for 10% of latest points)
* the library is part of rank_and_points.js 
* it will be executed after all the ranking / points calculation processes are finished
* after ranks.update() is done.
*/

//please note that, we process 1 team at a time.
var config = {};


exports.setConfig = function(c){
	config = c;
}
//adding cash
function adding_cash(conn,fb_id,transaction_name,amount,details,callback){
	conn.query("INSERT INTO "+config.database.frontend_schema+".game_transactions\
				(fb_id,transaction_dt,transaction_name,amount,details)\
				VALUES\
				(?,NOW(),?,?,?)\
				ON DUPLICATE KEY UPDATE\
				amount = VALUES(amount);",
				[fb_id,transaction_name,amount,details],
				function(err,rs){
					console.log(S(this.sql).collapseWhitespace().s);
					callback(err,rs);
				});
}

exports.adding_cash = adding_cash;

//updating the team's cash wallet by summing all cash amounts
function update_cash_summary(conn,fb_id,callback){
	conn.query("INSERT INTO "+config.database.frontend_schema+".game_team_cash\
				(fb_id,cash)\
				SELECT fb_id,SUM(amount) AS cash \
				FROM "+config.database.frontend_schema+".game_transactions\
				WHERE fb_id = ?\
				GROUP BY fb_id\
				ON DUPLICATE KEY UPDATE\
				cash = VALUES(cash);",[fb_id],function(err,rs){
					callback(err,rs);
				});
}
exports.update_cash_summary = update_cash_summary;
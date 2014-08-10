var path = require('path');
var users = require(path.resolve('./libs/api/users'));

var config = {};
var league = '';
exports.setConfig = function(c){
	config = c;
	users.setConfig(config);

}
exports.setLeague = function(l){
	league = l;
	users.setLeague = league;
}
exports.setPool = function(pool){
	users.setPool(pool);
}
exports.register = function(req,res){
	//req.body.name = req.body.name || '';
	//req.body.email = req.body.email || '';
	//req.body.phone = req.body.phone || '';
	users.register({
			name:req.body.name,
			email:req.body.email,
			fb_id:req.body.fb_id,
			phone:req.body.phone
		},
		function(err,rs){
			if(rs!=null){
				console.log('REGISTER','SUCCESS',req.body);
				res.send(200,{status:1,message:'the user is successfully registered !'});
			}else{
				console.log('REGISTER','FAILED',req.body);
				res.send(200,{status:0,err:"the user cannot be registered because it's already exists"});
			}
	});
}

function handleError(res){
	res.send(501,{error:'no data available'});
}

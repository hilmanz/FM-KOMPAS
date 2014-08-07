/**
api for authentication
*/

var crypto = require('crypto');
var fs = require('fs');
var path = require('path');
var xmlparser = require('xml2json');
var async = require('async');

var mysql = require('mysql');
var dateFormat = require('dateformat');
var redis = require('redis');
var pool = {};

var config = {};

exports.setConfig = function(c){
	config = c;
	service = config.ecash;
}

function prepareDb(callback){
	pool.getConnection(function(err,conn){
		callback(conn);
	});
}


function authenticate(req,res){
	var api_key = req.body.api_key;
	var request_code = req.body.request_code;
	if(request_code==null){
		askForChallengeCode(req,res,api_key);
	}else{
		authenticateCode(req,res,api_key,request_code);
	}
}
function askForChallengeCode(req,res,api_key){
	
	prepareDb(function(conn){
		conn.query("SELECT * FROM "+config.database.database+".api_keys WHERE api_key = ? LIMIT 1",
					[api_key],function(err,rs){
						conn.release();
						
							if(!err){
								if(rs[0].api_key == api_key){
									console.log(req.session);
									req.session.challenge_code = {};
									req.session.challenge_code[api_key] = generateChallengeCode(api_key);
									
								    res.send(200,{challenge_code:req.session.challenge_code[api_key]});
								}else{
									res.send(401,{error:'Invalid API Key'});	
								}
							}else{
								res.send(401,{error:'Invalid API Key'});
							}
						
		});
	});
}
function authenticateCode(req,res,api_key,request_code){
	
	console.log('session',req.session);
	if(typeof req.session.challenge_code !== 'undefined'){
		prepareDb(function(conn){
			conn.query("SELECT * FROM "+config.database.database+".api_keys WHERE api_key = ? LIMIT 1",
						[api_key],function(err,rs){
							conn.release();
								if(!err){
									if(rs[0].api_key == api_key){
										var hash = getRequestCodeHash(rs[0].api_key,
																		req.session.challenge_code[api_key],
																		rs[0].secret_key);
										
										if(hash==request_code){
											var access_token = generateAccessToken(api_key,request_code);
											req.session.access_token = access_token;
											//save the newly created access_token to redis, and set expiry time to
											//1 hour
											var client = req.redisClient;
									    	client.set(access_token,'1');
									    	client.expire(access_token,60*60*3);
									    	res.send(200,{access_token:access_token});
										   
										}else{
											res.send(403,{err:'Access Denied !'});
										}
									}else{
										res.send(401,{error:'Invalid API Key'});	
									}
								}else{
									res.send(401,{error:'Invalid API Key'});
								}
							
			});
		});
	}else{
		res.send(403,{err:'wrong challenge code'});
	}
}
function generateAccessToken(api_key,request_code){
	var str = api_key+'-'+request_code+'-'+dateFormat(new Date(), "ddmmyyyyHHMM")+'-'+Math.random()*999999;
	return crypto.createHash('sha1').update(str).digest("hex");
}
function generateChallengeCode(api_key){
	var challenge_code = api_key+'-'+dateFormat(new Date(), "ddmmyyyyHHMM")+'-'+Math.random()*999999+'-'+Math.random()*999999;
	return crypto.createHash('sha1').update(challenge_code).digest("hex");
}
function getRequestCodeHash(api_key,challenge_code,secret_key){
	var str = api_key+'|'+challenge_code+'|'+secret_key;
	console.log(str);
	return crypto.createHash('sha1').update(str).digest("hex");	
}

exports.authenticate = authenticate;

exports.canAccess = function(req,res,next){
	var access_token;
	if(req.body.access_token!=null){
		access_token = req.body.access_token;
	}else{
		access_token = req.query.access_token;
	}
	if(access_token!=null){
		req.redisClient.get(access_token,function(err,rs){
			console.log(rs);
			if(rs==null){
				res.send(401,{error:'access denied !'});
			}else{
				next();
			}
		});
	}else{
		res.send(401,{error:'access denied !'});
	}
}
exports.checkSession = function(req,res,callback){
	var access_token;
	if(req.body.access_token!=null){
		access_token = req.body.access_token;
	}else{
		access_token = req.query.access_token;
	}
	if(access_token!=null){
		req.redisClient.get(access_token,function(err,rs){
			console.log(rs);
			if(rs==null){
				callback(false);
			}else{
				callback(true);
			}
		});
	}else{
		callback(false);
	}
}

exports.setPool = function(p){
	pool = p;
}
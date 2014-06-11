/**
api related to user management.
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
var pool = {};
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
function register(data,callback){
	
	prepareDb(function(conn){
		conn.query("INSERT INTO ffgame_wc.game_users\
					(name,email,phone,fb_id,n_status,access_key,register_date)\
					VALUES(?,?,?,?,?,?,NOW());",
					[data.name,data.email,data.phone,data.fb_id,1,''],function(err,rs){
						
						conn.release();
						callback(err,rs);
						
		});
	});
}
function removeByFbId(fb_id,callback){
	prepareDb(function(conn){
		conn.query("DELETE FROM ffgame_wc.game_users\
					WHERE fb_id = ?",
					[fb_id],function(err,rs){
						conn.release();
						callback(err,rs);
						
		});
	});
}

exports.register = register;
exports.removeByFbId = removeByFbId;
exports.setPool = function(p){
	pool = p;
}
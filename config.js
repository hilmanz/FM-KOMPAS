exports.config = {
	competition: {id:8,year:2011},
	updater_file_prefix: 'srml-',
	database:{
		host:'localhost',
		username:'root',
		password:'root',
		database:'ffgame'
	},
	port: 3002,
	redis:{
		host:'localhost',
		port:6379
	},
	environment: 'development' //change to production when go live.
};
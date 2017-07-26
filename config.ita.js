exports.config = {
	competition: {id:4,year:2013},
	updater_file_prefix: 'srml-',
	database:{
		host:'localhost',
		username:'root',
		password:'root',
		database:'ffgame_ita',
		statsdb:'ffgame_stats_ita',
		frontend_schema:'fantasy',
		optadb: 'optadb'
	},
	port: 3022,
	redis:{
		host:'localhost',
		port:6379
	},
	environment: 'development', //change to production when go live.
	job_server_rank:{host:'localhost',port:3098},
	job_server:{host:'localhost',port:3099},
	ecash :  {
		protocol:'https',
		host:'mandiri-ecash.com',
		username:'supersoccer',
		password: 'pass123456',
		returnUrl: 'http://localhost/fm_2014/merchandises/payment',
		returnUrl2: 'http://localhost/duf/supersoccer_fork/onlinecatalog/complete',
		returnUrl3: 'http://localhost/fm_2014/merchandises/pay/return',
		returnUrl4: 'http://localhost/duf/supersoccer_fork/onlinecatalog/pay/success'
	},
	mailer:{
		host:'http://localhost:3101',
		queue:'http://localhost:3101'
	},
	mailgun:{
		//user: "postmaster@sandbox6048e62f52c444e28b8529f4e62f0c1e.mailgun.org",
		//pass: "22q7hrefk9j8"
		from: "postmaster@mg.supersoccer.co.id",
		user: "postmaster@mg.supersoccer.co.id",
		pass: "7xr67iht6bu6"
	}
};
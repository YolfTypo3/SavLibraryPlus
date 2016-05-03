#
# Table structure for table 'tx_savlibraryplus_export_configuration'
#
CREATE TABLE tx_savlibraryplus_export_configuration (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,
	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,
	fe_group int(11) DEFAULT '0' NOT NULL,
	name tinytext NOT NULL,
	cid int(11) DEFAULT '0' NOT NULL,
	configuration text NOT NULL,
	
	PRIMARY KEY (uid),
	KEY parent (pid)
);
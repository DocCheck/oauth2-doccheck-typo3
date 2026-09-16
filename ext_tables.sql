CREATE TABLE pages (
    tx_oauth2docchecktypo3_requires_auth tinyint(4) unsigned DEFAULT '0' NOT NULL
);

CREATE TABLE tt_content (
    tx_oauth2docchecktypo3_requires_auth tinyint(4) unsigned DEFAULT '0' NOT NULL,
    tx_oauth2docchecktypo3_return_path varchar(2048) DEFAULT '' NOT NULL,
    tx_oauth2docchecktypo3_language varchar(2) DEFAULT '' NOT NULL
);

CREATE TABLE fe_users (
    tx_oauth2docchecktypo3_unique_id varchar(255) DEFAULT '' NOT NULL,
    KEY oauth2docchecktypo3_unique_id (tx_oauth2docchecktypo3_unique_id)
);

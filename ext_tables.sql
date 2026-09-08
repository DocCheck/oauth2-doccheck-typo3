CREATE TABLE pages (
    tx_oauth2docchecktypo3_requires_auth tinyint(4) unsigned DEFAULT '0' NOT NULL
);

CREATE TABLE tt_content (
    tx_oauth2docchecktypo3_requires_auth tinyint(4) unsigned DEFAULT '0' NOT NULL
);

CREATE TABLE fe_users (
    tx_oauth2docchecktypo3_unique_id varchar(255) DEFAULT '' NOT NULL,
    KEY oauth2docchecktypo3_unique_id (tx_oauth2docchecktypo3_unique_id)
);

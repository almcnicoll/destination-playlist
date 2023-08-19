CREATE TABLE users
(
id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
authmethod_id INT UNSIGNED NOT NULL,
identifier VARCHAR(200) NOT NULL,
created DATETIME,
modified DATETIME
)
ENGINE=INNODB
;

CREATE TABLE authmethods
(
id INT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
method_name VARCHAR(200) NOT NULL,
handler VARCHAR(200) NOT NULL,
image VARCHAR(200) NULL,
created datetime,
modified DATETIME
)
ENGINE=InnoDB
;
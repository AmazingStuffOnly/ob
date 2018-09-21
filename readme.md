*player*

    CREATE TABLE `player` (
     `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
     `balance` float unsigned NOT NULL DEFAULT '1000',
     PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8
    
*balance_transaction*

    CREATE TABLE `balance_transaction` (
     `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
     `player_id` int(10) unsigned NOT NULL,
     `amount` float unsigned NOT NULL,
     `amount_before` float unsigned NOT NULL,
     PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8
    
*bet*

    CREATE TABLE `bet` (
     `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
     `stake_amount` float unsigned NOT NULL,
     `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
     PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8
    
*bet_selections*

    CREATE TABLE `bet_selections` (
     `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
     `bet_id` int(10) unsigned NOT NULL,
     `selection_id` int(10) unsigned NOT NULL,
     `odds` float unsigned NOT NULL,
     PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8

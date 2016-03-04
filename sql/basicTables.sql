CREATE TABLE `userlocations` (
	`userID` int(11) NOT NULL,
	`systemID` int(11) NOT NULL DEFAULT '0',
	`knownSpace` tinyint(1) NOT NULL DEFAULT '0',
	`responseEmpty` tinyint(1) NOT NULL DEFAULT '0',
	`lastUpdated` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `usermatchesinfo` (
	`userID` int(11) NOT NULL,
	`matchDate` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `usermatcheslist` (
	`userID` int(11) NOT NULL,
	`matchedUser` int(11) NOT NULL,
	`distance` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `userreactions` (
	`userFrom` int(11) NOT NULL,
	`userTo` int(11) NOT NULL,
	`reactionPositive` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
	`userID` int(11) NOT NULL,
	`userName` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
	`profileBody` text COLLATE utf8mb4_unicode_ci NOT NULL,
	`profilePublic` tinyint(1) NOT NULL DEFAULT '0',
	`userPremium` tinyint(1) NOT NULL DEFAULT '0',
	`userPartner` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `usersessions` (
	`token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
	`userID` int(11) NOT NULL,
	`validUntil` datetime NOT NULL,
	`ip` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `usertokens` (
	`userID` int(11) NOT NULL,
	`refreshToken` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
	`accessToken` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
	`validUntil` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



ALTER TABLE `userlocations`
ADD PRIMARY KEY (`userID`);

ALTER TABLE `usermatchesinfo`
ADD PRIMARY KEY (`userID`);

ALTER TABLE `usermatcheslist`
ADD PRIMARY KEY (`userID`,`matchedUser`);

ALTER TABLE `userreactions`
ADD PRIMARY KEY (`userFrom`,`userTo`);

ALTER TABLE `users`
ADD PRIMARY KEY (`userID`);

ALTER TABLE `usersessions`
ADD PRIMARY KEY (`token`);

ALTER TABLE `usertokens`
ADD PRIMARY KEY (`userID`);
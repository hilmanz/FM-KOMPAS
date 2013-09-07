DELIMITER $$

USE `ffg`$$

DROP PROCEDURE IF EXISTS `recalculate_weekly_rank`$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `recalculate_weekly_rank`(IN game_id VARCHAR(32))
BEGIN 
DECLARE isDone BOOLEAN DEFAULT FALSE;
DECLARE i INT DEFAULT 1;
DECLARE a BIGINT(11);
DECLARE b INT(11);
DECLARE c VARCHAR(20);
DECLARE curs CURSOR FOR 
	SELECT a.team_id,a.points,a.game_id
	FROM weekly_points a
	INNER JOIN teams b
	ON a.team_id = b.id 
	WHERE a.game_id=game_id
	ORDER BY a.points DESC;
DECLARE CONTINUE HANDLER FOR NOT FOUND SET isDone = TRUE;
OPEN curs;
	SET isDone = FALSE;
	SET i = 1;
	REPEAT
		FETCH curs INTO a,b,c;
		IF a IS NOT NULL THEN
			UPDATE points SET rank = i WHERE team_id=a;
			INSERT INTO weekly_ranks
			(team_id,game_id,rank)
			VALUES
			(a,game_id,i)
			ON DUPLICATE KEY UPDATE
			rank = VALUES(rank);
		END IF;
		SET i = i + 1;
		SET a = NULL;
		SET b = NULL;
		SET c = NULL;
	UNTIL isDone END REPEAT;
CLOSE curs;
END$$

DELIMITER ;
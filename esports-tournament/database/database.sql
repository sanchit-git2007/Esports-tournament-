-- =======================================================
-- ESPORTS TOURNAMENT MANAGEMENT SYSTEM DATABASE
-- Database: esports_tournament
-- Target Game: BGMI (Battlegrounds Mobile India)
-- Author: TY BSc Computer Science Student Project
-- =======================================================

CREATE DATABASE IF NOT EXISTS `esports_tournament` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `esports_tournament`;

-- -------------------------------------------------------
-- 1. Table: users
-- Stores both Tournament Administrators and Players
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `role` ENUM('admin', 'player') NOT NULL DEFAULT 'player',
    `profile_image` VARCHAR(255) DEFAULT 'default_avatar.png',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- 2. Table: teams
-- Created and managed by player (Captain)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `teams` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `team_name` VARCHAR(100) NOT NULL UNIQUE,
    `team_tag` VARCHAR(10) NOT NULL,
    `logo` VARCHAR(255) DEFAULT 'default_team.png',
    `captain_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_teams_captain` FOREIGN KEY (`captain_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- 3. Table: team_members
-- Squad members assigned to a team with BGMI in-game details
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `team_members` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `team_id` INT NOT NULL,
    `user_id` INT DEFAULT NULL,
    `in_game_name` VARCHAR(100) NOT NULL,
    `in_game_id` VARCHAR(50) NOT NULL,
    `player_role` VARCHAR(50) DEFAULT 'Assaulter', -- e.g. Captain, IGL, Assaulter, Sniper, Support
    `joined_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_members_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_members_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- 4. Table: tournaments
-- Master tournament table supporting Public and Private events
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tournaments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tournament_name` VARCHAR(150) NOT NULL,
    `game` VARCHAR(50) NOT NULL DEFAULT 'BGMI',
    `description` TEXT DEFAULT NULL,
    `tournament_type` ENUM('public', 'private') NOT NULL DEFAULT 'public',
    `access_code` VARCHAR(50) DEFAULT NULL, -- Only required if tournament_type = 'private'
    `max_teams` INT NOT NULL DEFAULT 16,
    `registration_start` DATE NOT NULL,
    `registration_end` DATE NOT NULL,
    `tournament_date` DATE NOT NULL,
    `status` ENUM('upcoming', 'registration_open', 'registration_closed', 'ongoing', 'completed', 'cancelled') NOT NULL DEFAULT 'upcoming',
    `banner` VARCHAR(255) DEFAULT 'default_banner.jpg',
    `rules` TEXT DEFAULT NULL,
    `kill_points_rate` INT NOT NULL DEFAULT 1, -- Default 1 point per kill (configurable)
    `created_by` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_tournaments_admin` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- 5. Table: tournament_scoring_rules
-- Configurable BGMI Placement Points for each tournament
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tournament_scoring_rules` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tournament_id` INT NOT NULL,
    `placement` INT NOT NULL, -- 1st, 2nd, 3rd, etc.
    `points` INT NOT NULL,    -- 15 pts for 1st, 12 for 2nd, etc.
    CONSTRAINT `fk_scoring_tournament` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_tourney_placement` (`tournament_id`, `placement`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- 6. Table: tournament_registrations
-- Team applications with Admin Approval / Rejection status
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tournament_registrations` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tournament_id` INT NOT NULL,
    `team_id` INT NOT NULL,
    `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `registered_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `approved_at` TIMESTAMP NULL DEFAULT NULL,
    CONSTRAINT `fk_reg_tournament` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_reg_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_reg_team_tourney` (`tournament_id`, `team_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- 7. Table: matches
-- Scheduled matches with maps and protected Room ID / Password
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `matches` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tournament_id` INT NOT NULL,
    `match_number` INT NOT NULL,
    `match_name` VARCHAR(100) NOT NULL, -- e.g. "Match 1 - Erangel"
    `match_date` DATE NOT NULL,
    `match_time` TIME NOT NULL,
    `map` ENUM('Erangel', 'Miramar', 'Sanhok', 'Vikendi') NOT NULL DEFAULT 'Erangel',
    `room_id` VARCHAR(100) DEFAULT NULL,
    `room_password` VARCHAR(100) DEFAULT NULL,
    `status` ENUM('scheduled', 'live', 'completed', 'cancelled') NOT NULL DEFAULT 'scheduled',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_matches_tournament` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- 8. Table: match_results
-- Recorded kills and placements per team with automated points
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `match_results` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `match_id` INT NOT NULL,
    `team_id` INT NOT NULL,
    `placement` INT NOT NULL,
    `kills` INT NOT NULL DEFAULT 0,
    `placement_points` INT NOT NULL DEFAULT 0,
    `kill_points` INT NOT NULL DEFAULT 0,
    `total_points` INT NOT NULL DEFAULT 0,
    `entered_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_results_match` FOREIGN KEY (`match_id`) REFERENCES `matches` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_results_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_match_team_result` (`match_id`, `team_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------
-- 9. Table: certificates
-- Auto-generated Participation and Winner credentials
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `certificates` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `tournament_id` INT NOT NULL,
    `user_id` INT DEFAULT NULL,
    `team_id` INT NOT NULL,
    `certificate_type` ENUM('participation', 'winner', 'runner_up') NOT NULL DEFAULT 'participation',
    `certificate_number` VARCHAR(100) NOT NULL UNIQUE,
    `issue_date` DATE NOT NULL,
    CONSTRAINT `fk_cert_tournament` FOREIGN KEY (`tournament_id`) REFERENCES `tournaments` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cert_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_cert_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =======================================================
-- INITIAL SEED DATA
-- Default Admin & Demo Player credentials for instant testing
-- =======================================================

-- 1. Insert Administrator & Demo Players (Passwords: admin123, player123)
INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `role`) VALUES
(1, 'Tournament Administrator', 'admin@esportshub.com', '$2y$10$bFS85vvddLPI5Cdc0b98veeJ1QGHdHgiZv4FRzoUIDOQyoiuM1PEq', '9876543210', 'admin'),
(2, 'Naman Mathur (Mortal)', 'captain@soul.com', '$2y$10$HxFVTp9LGAzsA/Blr5vyv.HIXXe6O/OSCSRzqGevb0.og1oY7L2Ve', '9876543211', 'player'),
(3, 'Jonathan Amaral', 'jonathan@godlike.com', '$2y$10$HxFVTp9LGAzsA/Blr5vyv.HIXXe6O/OSCSRzqGevb0.og1oY7L2Ve', '9876543212', 'player');

-- 2. Insert Teams
INSERT INTO `teams` (`id`, `team_name`, `team_tag`, `captain_id`) VALUES
(1, 'Team Soul', 'SOUL', 2),
(2, 'GodLike Esports', 'GODL', 3);

-- 3. Insert Team Squad Members
INSERT INTO `team_members` (`team_id`, `user_id`, `in_game_name`, `in_game_id`, `player_role`) VALUES
(1, 2, 'SOUL_Mortal', '5123456789', 'IGL / Captain'),
(1, NULL, 'SOUL_Viper', '5123456790', 'Assaulter'),
(1, NULL, 'SOUL_Regaltos', '5123456791', 'Assaulter'),
(1, NULL, 'SOUL_Aman', '5123456792', 'Support'),
(2, 3, 'GODL_Jonathan', '5987654321', 'IGL / Captain'),
(2, NULL, 'GODL_Neyoo', '5987654322', 'Assaulter'),
(2, NULL, 'GODL_Zgod', '5987654323', 'Support'),
(2, NULL, 'GODL_Shadow', '5987654324', 'Sniper');

-- 4. Insert Tournaments (1 Public, 1 Private)
INSERT INTO `tournaments` (`id`, `tournament_name`, `game`, `description`, `tournament_type`, `access_code`, `max_teams`, `registration_start`, `registration_end`, `tournament_date`, `status`, `rules`, `kill_points_rate`, `created_by`) VALUES
(1, 'SIES BGMI Championship 2026', 'BGMI', 'Premier inter-college BGMI tournament featuring squad matches across Erangel and Miramar. Top 3 teams win cash prizes and verified university certificates.', 'public', NULL, 16, '2026-08-20', '2026-08-29', '2026-08-30', 'registration_open', 'Standard BGMI Esports 15-point scoring rule applies. Emulators, hacks, and iPad view are strictly banned.', 1, 1),
(2, 'Campus Clash BGMI League', 'BGMI', 'Exclusive departmental tournament for registered computer science students. Requires passcode for squad registration.', 'private', 'BGMI2026', 16, '2026-08-25', '2026-09-04', '2026-09-05', 'upcoming', 'Only registered university students allowed. Access code required during registration.', 1, 1);

-- 5. Insert Official Configurable BGMI Scoring Rules for Tournament 1 (15-point system)
INSERT INTO `tournament_scoring_rules` (`tournament_id`, `placement`, `points`) VALUES
(1, 1, 15),
(1, 2, 12),
(1, 3, 10),
(1, 4, 8),
(1, 5, 6),
(1, 6, 4),
(1, 7, 2),
(1, 8, 1),
(1, 9, 1),
(1, 10, 1),
(1, 11, 1),
(1, 12, 0),
(1, 13, 0),
(1, 14, 0),
(1, 15, 0),
(1, 16, 0);

-- Insert Official Scoring for Tournament 2
INSERT INTO `tournament_scoring_rules` (`tournament_id`, `placement`, `points`) VALUES
(2, 1, 15), (2, 2, 12), (2, 3, 10), (2, 4, 8),
(2, 5, 6),  (2, 6, 4),  (2, 7, 2),  (2, 8, 1),
(2, 9, 1),  (2, 10, 1), (2, 11, 1), (2, 12, 0),
(2, 13, 0), (2, 14, 0), (2, 15, 0), (2, 16, 0);

-- 6. Insert Approved Registrations for Demo
INSERT INTO `tournament_registrations` (`tournament_id`, `team_id`, `status`, `approved_at`) VALUES
(1, 1, 'approved', NOW()),
(1, 2, 'approved', NOW());

-- 7. Insert Scheduled Matches
INSERT INTO `matches` (`id`, `tournament_id`, `match_number`, `match_name`, `match_date`, `match_time`, `map`, `room_id`, `room_password`, `status`) VALUES
(1, 1, 1, 'Match 1 - Erangel Battle', '2026-08-30', '18:00:00', 'Erangel', '9845123', 'bgmi2026', 'scheduled'),
(2, 1, 2, 'Match 2 - Miramar Desert Storm', '2026-08-30', '19:00:00', 'Miramar', '9845124', 'bgmi2026', 'scheduled');

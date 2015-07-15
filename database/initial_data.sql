-- phpMyAdmin SQL Dump
-- version 3.4.10.1deb1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jul 15, 2015 at 02:45 PM
-- Server version: 5.5.43
-- PHP Version: 5.3.10-1ubuntu3.18

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `greenhouse`
--

--
-- Dumping data for table `actuators`
--

INSERT INTO `actuators` (`id`, `name`, `mode_id`, `status`) VALUES
(1, 'Primary Fan', 1, 'Manual On'),
(2, 'Relay 2', 1, 'Manual On'),
(3, 'Relay 3', 1, 'Manual On'),
(4, 'Relay 4', 3, 'Following Program: Off'),
(5, 'Ventilation Fan', 2, 'Manual Off'),
(6, 'Relay 6', 1, 'Manual On'),
(7, 'Relay 7', 2, 'Manual Off'),
(8, 'Relay 8', 2, 'Manual Off');

--
-- Dumping data for table `actuator_modes`
--

INSERT INTO `actuator_modes` (`id`, `name`) VALUES
(1, 'Manual On'),
(2, 'Manual Off'),
(3, 'Program'),
(4, 'Disabled');

--
-- Dumping data for table `graphs`
--

INSERT INTO `graphs` (`id`, `title`, `xaxis_title`, `yaxis_title`, `front_page`) VALUES
(1, 'Greenhouse A', 'Last 24 Hours', 'Temperature/Humidity ', 1),
(2, 'Greenhouse B', 'Last 24 Hours', 'Temperature/Humidity', 1),
(3, 'Seedbed', 'Last 24 Hours', 'Temperature/Moisture', 1);

--
-- Dumping data for table `graph_sensors`
--

INSERT INTO `graph_sensors` (`id`, `graph_id`, `sensor_id`) VALUES
(1, 1, 3),
(2, 1, 4),
(3, 1, 5),
(4, 1, 6),
(5, 2, 7),
(6, 2, 8),
(7, 2, 9),
(8, 2, 10),
(9, 3, 11),
(10, 3, 12);

--
-- Dumping data for table `permissions`
--

INSERT INTO `permissions` (`id`, `action`, `level`) VALUES
(1, 'live-stream/view', 0),
(2, 'live-stream/edit', 1),
(3, 'remote-control/view', 0),
(4, 'remote-control/edit', 1),
(5, 'sensors/view', 0),
(6, 'sensors/edit', 1),
(7, 'config/view', 0),
(8, 'config/general/view', 0),
(9, 'config/general/edit', 1),
(10, 'config/schedule/view', 0),
(11, 'config/schedule/edit', 1),
(12, 'config/rules/view', 0),
(13, 'config/rules/edit', 1),
(14, 'config/tests/view', 0),
(15, 'config/tests/edit', 1);

--
-- Dumping data for table `sensors`
--

INSERT INTO `sensors` (`id`, `name`, `log`) VALUES
(1, 'Time', 0),
(3, 'Greenhouse-A Upper Temp ', 1),
(4, 'Greenhouse-A Upper Humid', 1),
(5, 'Greenhouse-A Lower Temp ', 1),
(6, 'Greenhouse-A Lower Humid', 1),
(7, 'Greenhouse-B Upper Temp ', 1),
(8, 'Greenhouse-B Upper Humid', 1),
(9, 'Greenhouse-B Lower Temp', 1),
(10, 'Greenhouse-B Lower Humid', 1),
(11, 'Seedbed Temp', 1),
(12, 'Seedbed Moisture', 1);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

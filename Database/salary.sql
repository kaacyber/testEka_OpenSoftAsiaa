-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 28 Bulan Mei 2023 pada 06.25
-- Versi server: 10.4.24-MariaDB
-- Versi PHP: 7.4.29

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `salary`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `salary`
--

CREATE TABLE `salary` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `value` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- Dumping data untuk tabel `salary`
--

INSERT INTO `salary` (`id`, `employee_id`, `date`, `value`) VALUES
(1, 1, '2021-01-01 00:00:00', 5000),
(2, 2, '2021-01-01 00:00:00', 7000),
(3, 3, '2021-01-01 00:00:00', 6000),
(4, 4, '2021-01-01 00:00:00', 8000),
(5, 5, '2021-01-01 00:00:00', 9000),
(6, 1, '2021-02-01 00:00:00', 5500),
(7, 2, '2021-02-01 00:00:00', 7500),
(8, 3, '2021-02-01 00:00:00', 6500),
(9, 4, '2021-02-01 00:00:00', 8500),
(10, 5, '2021-02-01 00:00:00', 9500),
(11, 1, '2022-01-01 00:00:00', 6000),
(12, 2, '2022-01-01 00:00:00', 8000),
(13, 3, '2022-01-01 00:00:00', 7000),
(14, 4, '2022-01-01 00:00:00', 9000),
(15, 5, '2022-01-01 00:00:00', 10000);

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `salary`
--
ALTER TABLE `salary`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `salary`
--
ALTER TABLE `salary`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

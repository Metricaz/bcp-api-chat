-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 29-Dez-2022 às 19:55
-- Versão do servidor: 10.4.22-MariaDB
-- versão do PHP: 7.4.21

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `bpc_api`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `borrowers`
--

CREATE TABLE `borrowers` (
  `id` int(32) NOT NULL,
  `name` tinytext NOT NULL,
  `cpf` varchar(12) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Extraindo dados da tabela `borrowers`
--

INSERT INTO `borrowers` (`id`, `name`, `cpf`, `created_at`) VALUES
(1, 'Felipe Peixoto', '37652687802', '2022-12-28 15:54:28'),
(2, 'felipe2', '0001', '2022-12-28 16:22:22'),
(3, 'felipe2', '0001-66', '2022-12-28 16:25:58'),
(4, 'felipe2', '000166', '2022-12-28 16:27:27');

-- --------------------------------------------------------

--
-- Estrutura da tabela `borrower_metas`
--

CREATE TABLE `borrower_metas` (
  `id` int(11) NOT NULL,
  `id_borrowers` int(11) NOT NULL,
  `field` varchar(20) NOT NULL,
  `value` varchar(50) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Extraindo dados da tabela `borrower_metas`
--

INSERT INTO `borrower_metas` (`id`, `id_borrowers`, `field`, `value`, `created_at`) VALUES
(2, 1, 'term', '9', '2022-12-28 19:27:54');

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `borrowers`
--
ALTER TABLE `borrowers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cpf` (`cpf`);

--
-- Índices para tabela `borrower_metas`
--
ALTER TABLE `borrower_metas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_borrowers` (`id_borrowers`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `borrowers`
--
ALTER TABLE `borrowers`
  MODIFY `id` int(32) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `borrower_metas`
--
ALTER TABLE `borrower_metas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restrições para despejos de tabelas
--

--
-- Limitadores para a tabela `borrower_metas`
--
ALTER TABLE `borrower_metas`
  ADD CONSTRAINT `fk` FOREIGN KEY (`id_borrowers`) REFERENCES `borrowers` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

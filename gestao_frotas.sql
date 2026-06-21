-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 20-Jun-2026 às 16:06
-- Versão do servidor: 10.4.32-MariaDB
-- versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `gestao_frotas`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `abastecimentos`
--

CREATE TABLE `abastecimentos` (
  `id` int(11) NOT NULL,
  `veiculo_id` int(11) NOT NULL,
  `motorista_id` int(11) DEFAULT NULL,
  `data_abastecimento` date NOT NULL,
  `litros` decimal(10,2) NOT NULL DEFAULT 0.00,
  `custo_total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `odometro_km` int(11) DEFAULT NULL,
  `posto` varchar(180) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `abastecimentos`
--

INSERT INTO `abastecimentos` (`id`, `veiculo_id`, `motorista_id`, `data_abastecimento`, `litros`, `custo_total`, `odometro_km`, `posto`, `observacoes`, `criado_em`) VALUES
(1, 7, NULL, '2026-06-10', 250.00, 345.00, 1340000, 'Cepsa', '', '2026-06-10 12:50:16'),
(2, 7, NULL, '2026-06-08', 310.50, 512.33, 189820, 'Galp Porto Campanhã', 'Abastecimento antes da rota Porto-Lisboa.', '2026-06-11 19:05:50'),
(3, 8, NULL, '2026-06-09', 278.20, 459.03, 176430, 'Repsol Aveiro', 'Abastecimento com AdBlue verificado.', '2026-06-11 19:05:50'),
(4, 10, NULL, '2026-06-11', 295.00, 486.75, 141900, 'BP Braga Sul', 'Prepara??o para carga industrial.', '2026-06-11 19:05:50');

-- --------------------------------------------------------

--
-- Estrutura da tabela `avarias`
--

CREATE TABLE `avarias` (
  `id` int(11) NOT NULL,
  `veiculo_id` int(11) DEFAULT NULL,
  `motorista_id` int(11) DEFAULT NULL,
  `carga_id` int(11) DEFAULT NULL,
  `titulo` varchar(160) NOT NULL,
  `descricao` text DEFAULT NULL,
  `gravidade` enum('baixa','media','alta','critica') NOT NULL DEFAULT 'media',
  `status` enum('aberta','em_analise','em_reparacao','resolvida','cancelada') NOT NULL DEFAULT 'aberta',
  `lat` decimal(10,7) DEFAULT NULL,
  `lon` decimal(10,7) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `avarias_problemas`
--

CREATE TABLE `avarias_problemas` (
  `id` int(11) NOT NULL,
  `titulo` varchar(180) NOT NULL,
  `descricao` text NOT NULL,
  `prioridade` enum('Baixa','Média','Alta','Crítica') NOT NULL DEFAULT 'Média',
  `status` enum('Aberto','Em análise','Resolvido','Fechado') NOT NULL DEFAULT 'Aberto',
  `viatura_id` int(11) DEFAULT NULL,
  `carga_id` int(11) DEFAULT NULL,
  `reportado_por_id` int(11) DEFAULT NULL,
  `resolvido_por_id` int(11) DEFAULT NULL,
  `resposta_gestor` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `avarias_problemas`
--

INSERT INTO `avarias_problemas` (`id`, `titulo`, `descricao`, `prioridade`, `status`, `viatura_id`, `carga_id`, `reportado_por_id`, `resolvido_por_id`, `resposta_gestor`, `criado_em`, `atualizado_em`) VALUES
(18, 'Luz de avaria no painel', 'A luz de motor acendeu durante a rota Aveiro-Porto.', 'Alta', 'Aberto', 7, 2, 2, NULL, NULL, '2026-06-09 18:28:11', '2026-06-09 18:28:11'),
(19, 'Porta traseira com fecho preso', 'A porta traseira direita demora a trancar depois da descarga.', 'Média', 'Em análise', 10, 4, 3, NULL, 'A oficina foi notificada para verificação.', '2026-06-09 18:28:11', '2026-06-09 18:28:11'),
(20, 'Temperatura da caixa refrigerada instável', 'Oscilação entre 3 e 8 graus durante 20 minutos.', 'Crítica', 'Aberto', 8, 3, NULL, NULL, NULL, '2026-06-09 18:28:11', '2026-06-11 19:07:35'),
(21, 'Sensor de temperatura instável', 'A caixa refrigerada oscilou entre 2 e 7 graus durante a viagem.', 'Crítica', 'Resolvido', 8, 6, NULL, 1, '', '2026-06-11 19:05:50', '2026-06-15 07:59:02'),
(22, 'Ruído anormal no eixo traseiro', 'Motorista reportou vibracao ao circular acima dos 80 km/h.', 'Alta', 'Em análise', 10, 7, 3, NULL, 'Viatura marcada para verificao na oficina.', '2026-06-11 19:05:50', '2026-06-11 19:15:04'),
(23, 'Lâmpada lateral substituída', 'Falha de iluminacao lateral resolvida antes da proxima rota.', 'Baixa', 'Resolvido', 7, 8, 3, 3, 'Pea substituida e verificao concluda.', '2026-06-11 19:05:50', '2026-06-11 19:15:04');

-- --------------------------------------------------------

--
-- Estrutura da tabela `cargas`
--

CREATE TABLE `cargas` (
  `id` int(11) NOT NULL,
  `codigo_rastreio` varchar(100) DEFAULT NULL,
  `estado_carga` enum('Pendente','Em Trânsito','Entregue','Cancelada') NOT NULL DEFAULT 'Pendente',
  `descricao` text DEFAULT NULL,
  `tipo_carga` varchar(100) DEFAULT NULL,
  `peso_kg` decimal(12,3) DEFAULT 0.000,
  `volume_m3` decimal(12,3) DEFAULT 0.000,
  `quantidade_paletes` int(11) DEFAULT 0,
  `local_recolha` varchar(255) DEFAULT NULL,
  `data_hora_recolha_prevista` datetime DEFAULT NULL,
  `data_hora_recolha_real` datetime DEFAULT NULL,
  `local_entrega` varchar(255) DEFAULT NULL,
  `data_hora_entrega_prevista` datetime DEFAULT NULL,
  `data_hora_entrega_real` datetime DEFAULT NULL,
  `cliente_id` int(11) DEFAULT NULL,
  `valor_transporte` decimal(12,2) DEFAULT 0.00,
  `pago` tinyint(1) DEFAULT 0,
  `viatura_id` int(11) DEFAULT NULL,
  `motorista_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `cargas`
--

INSERT INTO `cargas` (`id`, `codigo_rastreio`, `estado_carga`, `descricao`, `tipo_carga`, `peso_kg`, `volume_m3`, `quantidade_paletes`, `local_recolha`, `data_hora_recolha_prevista`, `data_hora_recolha_real`, `local_entrega`, `data_hora_entrega_prevista`, `data_hora_entrega_real`, `cliente_id`, `valor_transporte`, `pago`, `viatura_id`, `motorista_id`, `created_at`) VALUES
(1, 'GG12321321', 'Em Trânsito', '123213', '123', 132.000, 213321.000, 123123, 'eadsad', '2028-10-12 12:21:00', '2026-06-14 12:47:07', '123123', '2029-03-12 12:23:00', NULL, NULL, 1231232.00, 1, 7, NULL, '2026-06-08 22:30:17'),
(2, 'FTL-2026-001', 'Entregue', 'Paletes de componentes eletrónicos para entrega urgente.', 'Eletrónica', 3400.500, 16.200, 8, 'Aveiro Tech Components, Aveiro', '2026-06-09 09:30:00', NULL, 'Porto Fresh Logistics, Porto', '2026-06-09 14:45:00', '2026-06-11 19:48:27', 4, 780.00, 0, 7, NULL, '2026-06-09 08:10:49'),
(3, 'FTL-2026-002', 'Entregue', 'Carga refrigerada com controlo de temperatura.', 'Refrigerada', 6200.000, 24.800, 14, 'Porto Fresh Logistics, Porto', '2026-06-09 07:15:00', NULL, 'Lisboa Retail Group, Lisboa', '2026-06-09 18:30:00', NULL, 2, 1450.00, 0, 8, NULL, '2026-06-09 08:10:49'),
(4, 'FTL-2026-003', 'Entregue', 'Produtos farmacêuticos acondicionados.', 'Farmacêutica', 1200.000, 8.500, 4, 'Coimbra Farma Distribuição, Coimbra', '2026-06-08 08:00:00', NULL, 'Braga Industrial SA, Braga', '2026-06-08 13:20:00', NULL, 6, 620.00, 1, 10, NULL, '2026-06-09 08:10:49'),
(5, 'FTL-2026-004', 'Entregue', 'Material industrial pesado para linha de montagem.', 'Industrial', 8900.000, 31.400, 18, 'Braga Industrial SA, Braga', '2026-06-10 10:00:00', NULL, 'Aveiro Tech Components, Aveiro', '2026-06-10 17:00:00', '2026-06-11 19:48:29', 3, 1180.00, 0, 7, NULL, '2026-06-09 08:10:49'),
(6, 'FTL-PAP-101', 'Entregue', 'Material hospitalar urgente para distribuicao regional.', 'Sade', 1800.000, 9.500, 5, 'Setúbal Pharma Hub, Setúbal', '2026-06-12 20:05:50', NULL, 'Lisboa Retail Group, Lisboa', '2026-06-13 01:05:50', NULL, 7, 540.00, 0, 8, NULL, '2026-06-11 19:05:50'),
(7, 'FTL-PAP-102', 'Entregue', 'Perfis metlicos para obra industrial.', 'Industrial', 7200.000, 28.000, 16, 'Viana Metal Works, Viana do Castelo', '2026-06-11 17:05:50', NULL, 'Braga Industrial SA, Braga', '2026-06-12 00:05:50', NULL, 8, 980.00, 0, 10, NULL, '2026-06-11 19:05:50'),
(8, 'FTL-PAP-103', 'Entregue', 'Produtos frescos com controlo de temperatura.', 'Refrigerada', 4200.000, 18.500, 10, 'Porto Fresh Logistics, Porto', '2026-06-09 20:05:50', '2026-06-09 20:05:50', 'Lisboa Retail Group, Lisboa', '2026-06-10 20:05:50', '2026-06-10 21:05:50', 2, 1250.00, 1, 7, NULL, '2026-06-11 19:05:50'),
(9, 'FTL-PAP-104', 'Entregue', 'Carga tecnolgica para exposio acadmica.', 'Eletrnica', 950.000, 6.200, 3, 'Aveiro Tech Components, Aveiro', '2026-06-13 20:05:50', NULL, 'Setúbal Pharma Hub, Setúbal', '2026-06-14 02:05:50', NULL, 7, 430.00, 0, 9, NULL, '2026-06-11 19:05:50'),
(10, 'FTL-PAP-105', 'Cancelada', 'Carga cancelada pelo cliente antes da recolha.', 'Diversos', 500.000, 3.000, 2, 'Lisboa Retail Group, Lisboa', '2026-06-14 20:05:50', NULL, 'Porto Fresh Logistics, Porto', '2026-06-15 03:05:50', NULL, 5, 0.00, 0, NULL, NULL, '2026-06-11 19:05:50');

-- --------------------------------------------------------

--
-- Estrutura da tabela `clientes`
--

CREATE TABLE `clientes` (
  `id` int(11) NOT NULL,
  `tipo_cliente` enum('Física','Jurídica') NOT NULL DEFAULT 'Física',
  `nome` varchar(255) NOT NULL,
  `responsavel_contacto` varchar(255) DEFAULT NULL,
  `nif_nipc` varchar(100) NOT NULL,
  `morada_fiscal` varchar(255) DEFAULT NULL,
  `codigo_postal` varchar(50) DEFAULT NULL,
  `localidade` varchar(100) DEFAULT NULL,
  `pais` varchar(100) DEFAULT NULL,
  `telefone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `limite_credito` decimal(12,2) DEFAULT 0.00,
  `prazo_pagamento_dias` int(11) DEFAULT 30,
  `estado_cliente` enum('Ativo','Bloqueado','Inativo') NOT NULL DEFAULT 'Ativo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `lat` decimal(10,7) DEFAULT NULL,
  `lon` decimal(10,7) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `clientes`
--

INSERT INTO `clientes` (`id`, `tipo_cliente`, `nome`, `responsavel_contacto`, `nif_nipc`, `morada_fiscal`, `codigo_postal`, `localidade`, `pais`, `telefone`, `email`, `website`, `limite_credito`, `prazo_pagamento_dias`, `estado_cliente`, `created_at`, `lat`, `lon`) VALUES
(2, 'Jurídica', 'Porto Fresh Logistics', 'Ana Ribeiro', '510900111', 'Rua do Freixo 1071', '4300-219', 'Porto', 'Portugal', '225100221', 'operacoes@portofresh.local', NULL, 18000.00, 30, 'Ativo', '2026-06-09 08:10:49', 41.1496000, -8.5855000),
(3, 'Jurídica', 'Braga Industrial SA', 'Carlos Martins', '510900222', 'Parque Industrial de Celeiros', '4705-414', 'Braga', 'Portugal', '253200410', 'logistica@bragaindustrial.local', NULL, 24000.00, 30, 'Ativo', '2026-06-09 08:10:49', 41.5332000, -8.4321000),
(4, 'Jurídica', 'Aveiro Tech Components', 'Sofia Costa', '510900333', 'Zona Industrial de Taboeira', '3800-055', 'Aveiro', 'Portugal', '234900501', 'supply@aveirotech.local', NULL, 12500.00, 30, 'Ativo', '2026-06-09 08:10:49', 40.6566000, -8.6208000),
(5, 'Jurídica', 'Lisboa Retail Group', 'Miguel Ferreira', '510900444', 'Avenida Infante Dom Henrique', '1950-421', 'Lisboa', 'Portugal', '218700300', 'rececao@lisboaretail.local', NULL, 30000.00, 30, 'Ativo', '2026-06-09 08:10:49', 38.7436000, -9.1028000),
(6, 'Jurídica', 'Coimbra Farma Distribuição', 'Helena Matos', '510900555', 'Rua da Sofia 91', '3000-389', 'Coimbra', 'Portugal', '239800100', 'compras@coimbrafarma.local', NULL, 9000.00, 30, 'Bloqueado', '2026-06-09 08:10:49', 40.2130000, -8.4292000),
(7, '', 'Setubal Pharma Hub', 'Ins Carvalho', '517800221', 'Parque Industrial BlueBiz, Lote 12', '2910-741', 'Setubal', 'Portugal', '265700430', 'logistica@setubalpharma.local', 'https://setubalpharma.local', 22000.00, 30, 'Ativo', '2026-06-11 19:05:50', 38.5244000, -8.8882000),
(8, '', 'Viana Metal Works', 'Rui Martins', '516420990', 'Zona Industrial do Neiva, Rua 3', '4935-232', 'Viana do Castelo', 'Portugal', '258700612', 'expedicao@vianametal.local', 'https://vianametal.local', 26000.00, 45, 'Ativo', '2026-06-11 19:05:50', 41.6932000, -8.8329000);

-- --------------------------------------------------------

--
-- Estrutura da tabela `gps_eventos`
--

CREATE TABLE `gps_eventos` (
  `id` int(11) NOT NULL,
  `veiculo_id` int(11) DEFAULT NULL,
  `motorista_id` int(11) DEFAULT NULL,
  `carga_id` int(11) DEFAULT NULL,
  `lat` decimal(10,7) NOT NULL,
  `lon` decimal(10,7) NOT NULL,
  `velocidade` decimal(8,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `historico_manutencoes_inspecoes`
--

CREATE TABLE `historico_manutencoes_inspecoes` (
  `id` int(11) NOT NULL,
  `equipamento_id` int(11) DEFAULT NULL,
  `tecnico_id` int(11) DEFAULT NULL,
  `tipo_acao` enum('Inspeção','Manutenção Preventiva','Manutenção Corretiva','Preditiva') NOT NULL DEFAULT 'Inspeção',
  `status` enum('Agendado','Em Andamento','Concluído','Cancelado') NOT NULL DEFAULT 'Agendado',
  `prioridade` enum('Baixa','Média','Alta','Crítica') NOT NULL DEFAULT 'Média',
  `data_agendada` date DEFAULT NULL,
  `data_inicio` datetime DEFAULT NULL,
  `data_fim` datetime DEFAULT NULL,
  `proxima_revisao` date DEFAULT NULL,
  `descricao_problema` text DEFAULT NULL,
  `acoes_realizadas` text DEFAULT NULL,
  `resultado_inspecao` enum('Aprovado','Aprovado com Restrições','Reprovado') DEFAULT NULL,
  `leitura_odometro_horas` decimal(12,2) DEFAULT NULL,
  `custo_pecas` decimal(10,2) DEFAULT 0.00,
  `custo_mao_de_obra` decimal(10,2) DEFAULT 0.00,
  `custo_total` decimal(10,2) DEFAULT 0.00,
  `url_relatorio_pdf` varchar(255) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `historico_manutencoes_inspecoes`
--

INSERT INTO `historico_manutencoes_inspecoes` (`id`, `equipamento_id`, `tecnico_id`, `tipo_acao`, `status`, `prioridade`, `data_agendada`, `data_inicio`, `data_fim`, `proxima_revisao`, `descricao_problema`, `acoes_realizadas`, `resultado_inspecao`, `leitura_odometro_horas`, `custo_pecas`, `custo_mao_de_obra`, `custo_total`, `url_relatorio_pdf`, `criado_em`, `atualizado_em`) VALUES
(1, 7, 3, 'Inspeção', 'Concluído', 'Média', '2026-06-03', '2026-06-03 09:00:00', '2026-06-03 10:15:00', NULL, 'Inspecao periodica de seguranca.', 'Verificacao geral de luzes, pneus, travoes e documentos.', 'Aprovado', NULL, 0.00, 72.50, 72.50, NULL, '2026-06-11 10:12:37', '2026-06-11 10:12:37'),
(2, 8, 3, 'Inspeção', 'Concluído', 'Alta', '2026-06-05', '2026-06-05 14:00:00', '2026-06-05 15:30:00', NULL, 'Inspecao do tacografo antes de rota internacional.', 'Tacografo validado e sensores verificados.', 'Aprovado', NULL, 18.40, 95.00, 113.40, NULL, '2026-06-11 10:12:37', '2026-06-11 10:12:37'),
(3, 9, NULL, 'Inspeção', 'Concluído', 'Crítica', '2026-06-07', '2026-06-07 08:30:00', '2026-06-07 10:20:00', NULL, 'Inspecao apos alerta de travagem.', 'Teste de travoes realizado; pastilhas verificadas.', 'Aprovado com Restrições', NULL, 42.00, 120.00, 162.00, NULL, '2026-06-11 10:12:37', '2026-06-11 10:12:37'),
(4, 10, 3, 'Preditiva', 'Concluído', 'Baixa', '2026-06-09', '2026-06-09 11:00:00', '2026-06-09 12:05:00', NULL, 'Inspecao geral antes de revisao preventiva.', 'Niveis, pneus, luzes e chassis verificados.', 'Aprovado', NULL, 0.00, 68.75, 68.75, '', '2026-06-11 10:12:37', '2026-06-20 12:35:06'),
(5, 7, 3, 'Manutenção Preventiva', 'Agendado', 'Crítica', '2026-05-30', '2026-05-30 20:05:00', '2026-05-30 22:05:00', NULL, 'Revisao preventiva dos 190.000 km.', 'Oleo, filtros e diagnostico eletronico realizados.', 'Aprovado', NULL, 84.00, 110.00, 194.00, '', '2026-06-11 19:05:50', '2026-06-20 12:35:02'),
(6, 8, 3, 'Inspeção', 'Em Andamento', 'Alta', '2026-06-06', '2026-06-06 20:05:00', '2026-06-06 21:35:00', NULL, 'Inspecao de caixa refrigerada.', 'Sensor calibrado e relatorio validado.', '', NULL, 32.00, 95.00, 127.00, '', '2026-06-11 19:05:50', '2026-06-20 12:34:35'),
(7, 8, NULL, 'Manutenção Corretiva', 'Concluído', 'Média', '2026-06-11', NULL, '2026-06-15 08:58:00', NULL, 'Validar registos antes da proxima rota internacional.', 'Concluído a partir das manutenções pendentes.', '', NULL, 0.00, 4444.00, 4444.00, '', '2026-06-15 07:58:22', '2026-06-20 12:34:57');

-- --------------------------------------------------------

--
-- Estrutura da tabela `manutencoes`
--

CREATE TABLE `manutencoes` (
  `id` int(11) NOT NULL,
  `veiculo_id` int(11) NOT NULL,
  `tipo` varchar(100) NOT NULL,
  `status` enum('pendente','concluida') NOT NULL DEFAULT 'pendente',
  `criticidade` enum('critico','alto','medio','baixo') NOT NULL DEFAULT 'medio',
  `descricao` text DEFAULT NULL,
  `data_agendada` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `manutencoes`
--

INSERT INTO `manutencoes` (`id`, `veiculo_id`, `tipo`, `status`, `criticidade`, `descricao`, `data_agendada`) VALUES
(17, 9, 'Revisao de travoes', 'concluida', 'critico', 'Ruido ao travar reportado na ultima viagem.', '2026-06-10'),
(18, 7, 'Mudanca de oleo', 'pendente', 'medio', 'Servico preventivo aos 185.000 km.', '2026-06-12'),
(19, 8, 'Inspecao tacografo', 'concluida', 'alto', 'Validar registos antes da proxima rota internacional.', '2026-06-11'),
(20, 8, 'Revisao do sistema de frio', 'pendente', 'alto', 'Verificar compressor, sensores e vedantes da caixa refrigerada.', '2026-06-13'),
(21, 10, 'Alinhamento e suspensao', 'pendente', 'medio', 'Correcao preventiva apos vibracao reportada.', '2026-06-15');

-- --------------------------------------------------------

--
-- Estrutura da tabela `motoristas`
--

CREATE TABLE `motoristas` (
  `id` int(11) NOT NULL,
  `nome_completo` varchar(255) NOT NULL,
  `data_nascimento` date DEFAULT NULL,
  `telefone` varchar(50) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `nif` varchar(50) DEFAULT NULL,
  `numero_carta_conducao` varchar(100) DEFAULT NULL,
  `validade_carta` date DEFAULT NULL,
  `categoria_carta` varchar(50) DEFAULT NULL,
  `numero_cam_cqm` varchar(100) DEFAULT NULL,
  `validade_cam` date DEFAULT NULL,
  `numero_mecanografico` varchar(100) DEFAULT NULL,
  `data_admissao` date DEFAULT NULL,
  `tipo_contrato` varchar(100) DEFAULT NULL,
  `estado` enum('Ativo','De Férias','Baixa Médica','Inativo') DEFAULT 'Ativo',
  `disponibilidade` enum('Disponível','Em Viagem') DEFAULT 'Disponível',
  `viatura_atual_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `motoristas`
--

INSERT INTO `motoristas` (`id`, `nome_completo`, `data_nascimento`, `telefone`, `email`, `nif`, `numero_carta_conducao`, `validade_carta`, `categoria_carta`, `numero_cam_cqm`, `validade_cam`, `numero_mecanografico`, `data_admissao`, `tipo_contrato`, `estado`, `disponibilidade`, `viatura_atual_id`, `user_id`, `created_at`) VALUES
(8, 'João Lopes', NULL, '931665239', 'joaopedrolopes520@gmail.com', '131321312', '213213213213', '2028-03-12', 'C+E', '413123423', '2027-05-12', '234234324324', '2000-01-10', 'Contrato de trabalho sem termo', 'Ativo', 'Disponível', 7, 2, '2026-06-15 08:01:53');

-- --------------------------------------------------------

--
-- Estrutura da tabela `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(128) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `expires_at`, `created_at`) VALUES
(1, 'admin@frota.local', '672fd4541d6b8d5b870c7a7e54913365', '2026-06-11 21:49:42', '2026-06-11 18:49:42');

-- --------------------------------------------------------

--
-- Estrutura da tabela `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `users`
--

INSERT INTO `users` (`id`, `username`, `password_hash`, `name`, `created_at`) VALUES
(1, 'admin', '$2y$10$FElRbbsuN6XUf4numepRc.cXA.wgEdGnPzr27nD7viX5feZSHWnRe', 'Administrador', '2026-06-07 13:32:27');

-- --------------------------------------------------------

--
-- Estrutura da tabela `utilizadores`
--

CREATE TABLE `utilizadores` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `nivel_acesso` enum('admin','gestor','motorista') NOT NULL DEFAULT 'motorista',
  `status` enum('ativo','inativo') NOT NULL DEFAULT 'ativo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `utilizadores`
--

INSERT INTO `utilizadores` (`id`, `nome`, `email`, `password_hash`, `nivel_acesso`, `status`, `created_at`) VALUES
(1, 'Administrador', 'admin@frota.local', '$2y$10$18W7x7XN5VyL1SJ36S0TVe9KndWLjqJx8LW9lEa6yyLcrnRiLff8e', 'admin', 'ativo', '2026-06-07 13:48:15'),
(2, 'João Lopes', 'motorista@frota.local', '$2y$10$tCQh9PbOiYJwbkaGbU.YduutrgMiVZ0nmDGUf9TXCtUYV5OU8yTqC', 'motorista', 'ativo', '2026-06-08 08:58:55'),
(3, 'Gestor Operacional', 'gestor@frota.local', '$2y$10$CdiVn9NQTMAnGhLlwXnNFezJsUuTRAhN3xx.L6f/AKv5Esu3Qc7AO', 'gestor', 'ativo', '2026-06-08 22:34:55');

-- --------------------------------------------------------

--
-- Estrutura da tabela `veiculos`
--

CREATE TABLE `veiculos` (
  `id` int(11) NOT NULL,
  `matricula` varchar(20) NOT NULL,
  `modelo` varchar(100) NOT NULL,
  `status` enum('ativo','em_manutencao','ocioso') NOT NULL DEFAULT 'ativo',
  `km_total` int(11) NOT NULL DEFAULT 0,
  `consumo_medio` decimal(4,1) NOT NULL DEFAULT 0.0,
  `lat` decimal(10,7) DEFAULT NULL,
  `lon` decimal(10,7) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `veiculos`
--

INSERT INTO `veiculos` (`id`, `matricula`, `modelo`, `status`, `km_total`, `consumo_medio`, `lat`, `lon`, `updated_at`) VALUES
(7, '30-AG-FC', 'Scania R560 V8', 'ativo', 1340000, 29.8, 41.2035210, -8.3429040, '2026-06-14 11:46:45'),
(8, '82-TL-19', 'Volvo FH 500', 'ativo', 176430, 27.4, 41.5454000, -8.4265000, '2026-06-11 19:05:50'),
(9, '15-MT-44', 'Mercedes Actros 1845', 'em_manutencao', 213700, 31.2, 40.6405000, -8.6538000, '2026-06-09 08:11:49'),
(10, '67-RD-02', 'MAN TGX 18.470', 'ocioso', 141900, 26.1, 38.7223000, -9.1393000, '2026-06-11 19:05:50');

-- --------------------------------------------------------

--
-- Estrutura da tabela `viagens`
--

CREATE TABLE `viagens` (
  `id` int(11) NOT NULL,
  `veiculo_id` int(11) NOT NULL,
  `data_viagem` date NOT NULL,
  `distancia_km` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `viagens`
--

INSERT INTO `viagens` (`id`, `veiculo_id`, `data_viagem`, `distancia_km`) VALUES
(57, 7, '2026-06-09', 186),
(58, 8, '2026-06-09', 96),
(59, 7, '2026-06-08', 242),
(60, 8, '2026-06-08', 165),
(61, 7, '2026-06-07', 155),
(62, 8, '2026-06-07', 210),
(63, 7, '2026-06-06', 310),
(64, 8, '2026-06-06', 144),
(65, 7, '2026-06-05', 278),
(66, 8, '2026-06-05', 320),
(67, 7, '2026-06-04', 420),
(68, 8, '2026-06-04', 180),
(69, 7, '2026-06-03', 198),
(70, 8, '2026-06-03', 260);

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `abastecimentos`
--
ALTER TABLE `abastecimentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `veiculo_id` (`veiculo_id`),
  ADD KEY `motorista_id` (`motorista_id`);

--
-- Índices para tabela `avarias`
--
ALTER TABLE `avarias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `veiculo_id` (`veiculo_id`),
  ADD KEY `motorista_id` (`motorista_id`),
  ADD KEY `carga_id` (`carga_id`);

--
-- Índices para tabela `avarias_problemas`
--
ALTER TABLE `avarias_problemas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `viatura_id` (`viatura_id`),
  ADD KEY `carga_id` (`carga_id`),
  ADD KEY `reportado_por_id` (`reportado_por_id`),
  ADD KEY `resolvido_por_id` (`resolvido_por_id`);

--
-- Índices para tabela `cargas`
--
ALTER TABLE `cargas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo_rastreio` (`codigo_rastreio`),
  ADD KEY `viatura_id` (`viatura_id`),
  ADD KEY `motorista_id` (`motorista_id`);

--
-- Índices para tabela `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nif_nipc` (`nif_nipc`);

--
-- Índices para tabela `gps_eventos`
--
ALTER TABLE `gps_eventos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `veiculo_id` (`veiculo_id`),
  ADD KEY `motorista_id` (`motorista_id`),
  ADD KEY `carga_id` (`carga_id`);

--
-- Índices para tabela `historico_manutencoes_inspecoes`
--
ALTER TABLE `historico_manutencoes_inspecoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `equipamento_id` (`equipamento_id`),
  ADD KEY `tecnico_id` (`tecnico_id`);

--
-- Índices para tabela `manutencoes`
--
ALTER TABLE `manutencoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `veiculo_id` (`veiculo_id`);

--
-- Índices para tabela `motoristas`
--
ALTER TABLE `motoristas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `viatura_atual_id` (`viatura_atual_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Índices para tabela `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Índices para tabela `utilizadores`
--
ALTER TABLE `utilizadores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices para tabela `veiculos`
--
ALTER TABLE `veiculos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `matricula` (`matricula`) USING BTREE;

--
-- Índices para tabela `viagens`
--
ALTER TABLE `viagens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `veiculo_id` (`veiculo_id`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `abastecimentos`
--
ALTER TABLE `abastecimentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `avarias`
--
ALTER TABLE `avarias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `avarias_problemas`
--
ALTER TABLE `avarias_problemas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de tabela `cargas`
--
ALTER TABLE `cargas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `clientes`
--
ALTER TABLE `clientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `gps_eventos`
--
ALTER TABLE `gps_eventos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `historico_manutencoes_inspecoes`
--
ALTER TABLE `historico_manutencoes_inspecoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `manutencoes`
--
ALTER TABLE `manutencoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT de tabela `motoristas`
--
ALTER TABLE `motoristas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `utilizadores`
--
ALTER TABLE `utilizadores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `veiculos`
--
ALTER TABLE `veiculos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `viagens`
--
ALTER TABLE `viagens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=71;

--
-- Restrições para despejos de tabelas
--

--
-- Limitadores para a tabela `abastecimentos`
--
ALTER TABLE `abastecimentos`
  ADD CONSTRAINT `abastecimentos_ibfk_1` FOREIGN KEY (`veiculo_id`) REFERENCES `veiculos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `abastecimentos_ibfk_2` FOREIGN KEY (`motorista_id`) REFERENCES `motoristas` (`id`) ON DELETE SET NULL;

--
-- Limitadores para a tabela `avarias`
--
ALTER TABLE `avarias`
  ADD CONSTRAINT `avarias_ibfk_1` FOREIGN KEY (`veiculo_id`) REFERENCES `veiculos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `avarias_ibfk_2` FOREIGN KEY (`motorista_id`) REFERENCES `motoristas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `avarias_ibfk_3` FOREIGN KEY (`carga_id`) REFERENCES `cargas` (`id`) ON DELETE SET NULL;

--
-- Limitadores para a tabela `avarias_problemas`
--
ALTER TABLE `avarias_problemas`
  ADD CONSTRAINT `avarias_problemas_ibfk_1` FOREIGN KEY (`viatura_id`) REFERENCES `veiculos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `avarias_problemas_ibfk_2` FOREIGN KEY (`carga_id`) REFERENCES `cargas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `avarias_problemas_ibfk_3` FOREIGN KEY (`reportado_por_id`) REFERENCES `utilizadores` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `avarias_problemas_ibfk_4` FOREIGN KEY (`resolvido_por_id`) REFERENCES `utilizadores` (`id`) ON DELETE SET NULL;

--
-- Limitadores para a tabela `cargas`
--
ALTER TABLE `cargas`
  ADD CONSTRAINT `cargas_ibfk_1` FOREIGN KEY (`viatura_id`) REFERENCES `veiculos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `cargas_ibfk_2` FOREIGN KEY (`motorista_id`) REFERENCES `motoristas` (`id`) ON DELETE SET NULL;

--
-- Limitadores para a tabela `gps_eventos`
--
ALTER TABLE `gps_eventos`
  ADD CONSTRAINT `gps_eventos_ibfk_1` FOREIGN KEY (`veiculo_id`) REFERENCES `veiculos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `gps_eventos_ibfk_2` FOREIGN KEY (`motorista_id`) REFERENCES `motoristas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `gps_eventos_ibfk_3` FOREIGN KEY (`carga_id`) REFERENCES `cargas` (`id`) ON DELETE SET NULL;

--
-- Limitadores para a tabela `historico_manutencoes_inspecoes`
--
ALTER TABLE `historico_manutencoes_inspecoes`
  ADD CONSTRAINT `historico_manutencoes_inspecoes_ibfk_1` FOREIGN KEY (`equipamento_id`) REFERENCES `veiculos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `historico_manutencoes_inspecoes_ibfk_2` FOREIGN KEY (`tecnico_id`) REFERENCES `utilizadores` (`id`) ON DELETE SET NULL;

--
-- Limitadores para a tabela `manutencoes`
--
ALTER TABLE `manutencoes`
  ADD CONSTRAINT `manutencoes_ibfk_1` FOREIGN KEY (`veiculo_id`) REFERENCES `veiculos` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `motoristas`
--
ALTER TABLE `motoristas`
  ADD CONSTRAINT `motoristas_ibfk_1` FOREIGN KEY (`viatura_atual_id`) REFERENCES `veiculos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `motoristas_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `utilizadores` (`id`) ON DELETE SET NULL;

--
-- Limitadores para a tabela `viagens`
--
ALTER TABLE `viagens`
  ADD CONSTRAINT `viagens_ibfk_1` FOREIGN KEY (`veiculo_id`) REFERENCES `veiculos` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

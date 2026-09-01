/* ============================================================
   PETFINDER BRASIL
   DATABASE
   ============================================================
   Gerado a partir de um dump real do banco em producao/dev,
   com os dados de producao removidos (usuarios, pets, prontuarios,
   conversas, pedidos, auditoria, etc.) e mantendo apenas dados de
   referencia/catalogo (especies, racas, categorias, subcategorias,
   marcas, formas_pagamento, especialidades, vacinas, faq,
   configuracoes, banners, cupons de exemplo).

   Substitui a versao anterior deste arquivo, que estava desatualizada
   e nao incluia as colunas `pets.status`, `usuarios.tipo_usuario` e a
   tabela `pet_alertas_perdidos` -- o que fazia parte da suite de
   testes (tests/run_all.php) falhar (18 de 43 testes).

   Validado: importa sem erros e os 43 testes de tests/run_all.php
   passam contra este schema.
   ============================================================ */

CREATE DATABASE IF NOT EXISTS petfinder
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE petfinder;

-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 31/08/2026 às 23:39
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `petfinder`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `agenda_veterinaria`
--

CREATE TABLE `agenda_veterinaria` (
  `id` int(11) NOT NULL,
  `veterinario_id` int(11) NOT NULL,
  `data` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fim` time NOT NULL,
  `disponivel` tinyint(1) DEFAULT 1,
  `observacoes` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `alergias`
--

CREATE TABLE `alergias` (
  `id` int(11) NOT NULL,
  `pet_id` int(11) NOT NULL,
  `descricao` text NOT NULL,
  `severidade` enum('Baixa','Média','Alta') DEFAULT 'Baixa'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `auditoria`
--

CREATE TABLE `auditoria` (
  `id` bigint(20) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `tabela` varchar(120) DEFAULT NULL,
  `acao` enum('INSERT','UPDATE','DELETE') DEFAULT NULL,
  `registro_id` int(11) DEFAULT NULL,
  `detalhes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `auditoria`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `avaliacoes`
--

CREATE TABLE `avaliacoes` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `empresa_id` int(11) DEFAULT NULL,
  `produto_id` int(11) DEFAULT NULL,
  `veterinario_id` int(11) DEFAULT NULL,
  `nota` tinyint(4) NOT NULL,
  `titulo` varchar(200) DEFAULT NULL,
  `comentario` text DEFAULT NULL,
  `recomendado` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `banners`
--

CREATE TABLE `banners` (
  `id` int(11) NOT NULL,
  `titulo` varchar(180) DEFAULT NULL,
  `subtitulo` varchar(255) DEFAULT NULL,
  `imagem` varchar(255) DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `ordem` int(11) DEFAULT 1,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `banners`
--

INSERT INTO `banners` (`id`, `titulo`, `subtitulo`, `imagem`, `link`, `ordem`, `ativo`, `criado_em`) VALUES
(1, 'Bem-vindo ao PetFinder Brasil', 'A maior plataforma para quem ama pets.', 'banner1.jpg', NULL, 1, 1, '2026-07-29 23:04:45'),
(2, 'Marketplace Pet', 'Milhares de produtos com entrega rápida.', 'banner2.jpg', NULL, 2, 1, '2026-07-29 23:04:45'),
(3, 'Veterinários Próximos', 'Agende consultas em poucos minutos.', 'banner3.jpg', NULL, 3, 1, '2026-07-29 23:04:45');

-- --------------------------------------------------------

--
-- Estrutura para tabela `blog_comentarios`
--

CREATE TABLE `blog_comentarios` (
  `id` int(11) NOT NULL,
  `post_id` tinyint(4) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `comentario` text NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `blog_shares`
--

CREATE TABLE `blog_shares` (
  `id` int(11) NOT NULL,
  `post_id` tinyint(4) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `rede_social` varchar(60) NOT NULL,
  `compartilhado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `carrinho`
--

CREATE TABLE `carrinho` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `carrinho_itens`
--

CREATE TABLE `carrinho_itens` (
  `id` int(11) NOT NULL,
  `carrinho_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL DEFAULT 1,
  `preco_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `nome` varchar(120) NOT NULL,
  `descricao` text DEFAULT NULL,
  `icone` varchar(120) DEFAULT NULL,
  `imagem` varchar(255) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `categorias`
--

INSERT INTO `categorias` (`id`, `nome`, `descricao`, `icone`, `imagem`, `ativo`, `criado_em`) VALUES
(1, 'Pet Shop', 'Lojas especializadas', 'bi-shop', NULL, 1, '2026-07-29 23:04:41'),
(2, 'Clínica Veterinária', 'Consultas e exames', 'bi-heart-pulse', NULL, 1, '2026-07-29 23:04:41'),
(3, 'Hospital Veterinário', 'Atendimento 24 horas', 'bi-hospital', NULL, 1, '2026-07-29 23:04:41'),
(4, 'Banho e Tosa', 'Higiene e estética', 'bi-scissors', NULL, 1, '2026-07-29 23:04:41'),
(5, 'Hotel para Pets', 'Hospedagem', 'bi-house', NULL, 1, '2026-07-29 23:04:41'),
(6, 'Creche Pet', 'Day Care', 'bi-balloon-heart', NULL, 1, '2026-07-29 23:04:41'),
(7, 'Adestramento', 'Treinamento', 'bi-award', NULL, 1, '2026-07-29 23:04:41'),
(8, 'Adoção', 'ONGs e protetores', 'bi-heart', NULL, 1, '2026-07-29 23:04:41'),
(9, 'Marketplace', 'Produtos para pets', 'bi-cart', NULL, 1, '2026-07-29 23:04:41'),
(10, 'Serviços', 'Diversos', 'bi-grid', NULL, 1, '2026-07-29 23:04:41');

-- --------------------------------------------------------

--
-- Estrutura para tabela `configuracoes`
--

CREATE TABLE `configuracoes` (
  `id` int(11) NOT NULL,
  `chave_config` varchar(120) NOT NULL,
  `valor_config` text DEFAULT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `configuracoes`
--

INSERT INTO `configuracoes` (`id`, `chave_config`, `valor_config`, `descricao`, `atualizado_em`) VALUES
(1, 'nome_sistema', 'PetFinder Brasil', 'Nome da plataforma', '2026-07-29 23:04:45'),
(2, 'email_suporte', 'suporte@petfinder.com', 'Contato principal', '2026-07-29 23:04:45'),
(3, 'telefone', '(31) 99999-9999', 'Telefone principal', '2026-07-29 23:04:45'),
(4, 'manutencao', 'false', 'Modo manutenção', '2026-07-29 23:04:45'),
(5, 'versao', '1.0.0', 'Versão do sistema', '2026-07-29 23:04:45');

-- --------------------------------------------------------

--
-- Estrutura para tabela `consultas`
--

CREATE TABLE `consultas` (
  `id` int(11) NOT NULL,
  `veterinario_id` int(11) NOT NULL,
  `empresa_id` int(11) DEFAULT NULL,
  `usuario_id` int(11) NOT NULL,
  `pet_id` int(11) DEFAULT NULL,
  `data_consulta` date NOT NULL,
  `hora_consulta` time NOT NULL,
  `status` enum('Agendada','Confirmada','Em Atendimento','Concluída','Cancelada') DEFAULT 'Agendada',
  `motivo` text DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `valor` decimal(10,2) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `consultas`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `conversas`
--

CREATE TABLE `conversas` (
  `id` int(11) NOT NULL,
  `usuario_origem` int(11) NOT NULL,
  `usuario_destino` int(11) NOT NULL,
  `assunto` varchar(200) DEFAULT NULL,
  `status` enum('Aberta','Fechada','Arquivada') DEFAULT 'Aberta',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `conversas`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `cupons`
--

CREATE TABLE `cupons` (
  `id` int(11) NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `tipo` enum('Percentual','Valor') DEFAULT 'Percentual',
  `valor` decimal(10,2) NOT NULL,
  `valor_minimo` decimal(10,2) DEFAULT NULL,
  `data_inicio` date DEFAULT NULL,
  `data_fim` date DEFAULT NULL,
  `quantidade` int(11) DEFAULT 0,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `cupons`
--

INSERT INTO `cupons` (`id`, `codigo`, `descricao`, `tipo`, `valor`, `valor_minimo`, `data_inicio`, `data_fim`, `quantidade`, `ativo`, `criado_em`) VALUES
(1, 'BEMVINDO10', '10% de desconto na primeira compra', 'Percentual', 10.00, 50.00, NULL, NULL, 1000, 1, '2026-07-29 23:04:44'),
(2, 'FRETEGRATIS', 'Cupom promocional de lançamento', 'Valor', 20.00, 150.00, NULL, NULL, 500, 1, '2026-07-29 23:04:44');

-- --------------------------------------------------------

--
-- Estrutura para tabela `cupons_utilizados`
--

CREATE TABLE `cupons_utilizados` (
  `id` int(11) NOT NULL,
  `cupom_id` int(11) NOT NULL,
  `pedido_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `data_utilizacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `empresas`
--

CREATE TABLE `empresas` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `categoria_id` int(11) NOT NULL,
  `nome_fantasia` varchar(180) NOT NULL,
  `razao_social` varchar(180) DEFAULT NULL,
  `cnpj` char(14) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `whatsapp` varchar(20) DEFAULT NULL,
  `email` varchar(180) DEFAULT NULL,
  `site` varchar(255) DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `capa` varchar(255) DEFAULT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `complemento` varchar(120) DEFAULT NULL,
  `bairro` varchar(120) DEFAULT NULL,
  `cidade` varchar(120) DEFAULT NULL,
  `estado` char(2) DEFAULT NULL,
  `cep` varchar(9) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `avaliacao` decimal(2,1) DEFAULT 0.0,
  `total_avaliacoes` int(11) DEFAULT 0,
  `verificada` tinyint(1) DEFAULT 0,
  `ativo` tinyint(1) DEFAULT 1,
  `status_pagamento` enum('Ativo','Atrasado','Suspenso') NOT NULL DEFAULT 'Ativo',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `empresas`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `empresa_equipe`
--

CREATE TABLE `empresa_equipe` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `papel` enum('proprietario','administrador','veterinario','adestrador','atendente','financeiro') NOT NULL DEFAULT 'atendente',
  `status` enum('ativo','pendente','inativo') NOT NULL DEFAULT 'ativo',
  `convidado_por` int(11) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `empresa_equipe`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `empresa_galeria`
--

CREATE TABLE `empresa_galeria` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `imagem` varchar(255) NOT NULL,
  `legenda` varchar(255) DEFAULT NULL,
  `ordem` int(11) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `empresa_horarios`
--

CREATE TABLE `empresa_horarios` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `dia_semana` enum('Segunda','Terça','Quarta','Quinta','Sexta','Sábado','Domingo') NOT NULL,
  `abertura` time DEFAULT NULL,
  `fechamento` time DEFAULT NULL,
  `fechado` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `empresa_horarios`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `enderecos`
--

CREATE TABLE `enderecos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `cep` varchar(9) DEFAULT NULL,
  `logradouro` varchar(255) DEFAULT NULL,
  `numero` varchar(20) DEFAULT NULL,
  `complemento` varchar(255) DEFAULT NULL,
  `bairro` varchar(150) DEFAULT NULL,
  `cidade` varchar(150) DEFAULT NULL,
  `estado` char(2) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `principal` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `enderecos`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `especialidades`
--

CREATE TABLE `especialidades` (
  `id` int(11) NOT NULL,
  `nome` varchar(120) NOT NULL,
  `descricao` text DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `especialidades`
--

INSERT INTO `especialidades` (`id`, `nome`, `descricao`, `ativo`, `criado_em`) VALUES
(1, 'Clínico Geral', NULL, 1, '2026-07-29 23:04:42'),
(2, 'Cardiologia', NULL, 1, '2026-07-29 23:04:42'),
(3, 'Dermatologia', NULL, 1, '2026-07-29 23:04:42'),
(4, 'Ortopedia', NULL, 1, '2026-07-29 23:04:42'),
(5, 'Neurologia', NULL, 1, '2026-07-29 23:04:42'),
(6, 'Oftalmologia', NULL, 1, '2026-07-29 23:04:42'),
(7, 'Odontologia', NULL, 1, '2026-07-29 23:04:42'),
(8, 'Oncologia', NULL, 1, '2026-07-29 23:04:42'),
(9, 'Cirurgia', NULL, 1, '2026-07-29 23:04:42'),
(10, 'Animais Silvestres', NULL, 1, '2026-07-29 23:04:42'),
(11, 'Felinos', NULL, 1, '2026-07-29 23:04:42'),
(12, 'Caninos', NULL, 1, '2026-07-29 23:04:42'),
(13, 'Animais Exóticos', NULL, 1, '2026-07-29 23:04:42'),
(14, 'Diagnóstico por Imagem', NULL, 1, '2026-07-29 23:04:42'),
(15, 'Emergência 24 Horas', NULL, 1, '2026-07-29 23:04:42');

-- --------------------------------------------------------

--
-- Estrutura para tabela `especies`
--

CREATE TABLE `especies` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `especies`
--

INSERT INTO `especies` (`id`, `nome`, `ativo`, `criado_em`) VALUES
(1, 'Cachorro', 1, '2026-07-29 23:04:42'),
(2, 'Gato', 1, '2026-07-29 23:04:42'),
(3, 'Ave', 1, '2026-07-29 23:04:42'),
(4, 'Peixe', 1, '2026-07-29 23:04:42'),
(5, 'Coelho', 1, '2026-07-29 23:04:42'),
(6, 'Hamster', 1, '2026-07-29 23:04:42'),
(7, 'Porquinho-da-Índia', 1, '2026-07-29 23:04:42'),
(8, 'Réptil', 1, '2026-07-29 23:04:42'),
(9, 'Cavalo', 1, '2026-07-29 23:04:42'),
(10, 'Animal Silvestre', 1, '2026-07-29 23:04:42');

-- --------------------------------------------------------

--
-- Estrutura para tabela `estoque`
--

CREATE TABLE `estoque` (
  `id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `quantidade` int(11) DEFAULT 0,
  `estoque_minimo` int(11) DEFAULT 0,
  `estoque_maximo` int(11) DEFAULT 0,
  `ultima_atualizacao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `estoque`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `faq`
--

CREATE TABLE `faq` (
  `id` int(11) NOT NULL,
  `pergunta` varchar(255) NOT NULL,
  `resposta` text NOT NULL,
  `ordem` int(11) DEFAULT 1,
  `ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `faq`
--

INSERT INTO `faq` (`id`, `pergunta`, `resposta`, `ordem`, `ativo`) VALUES
(1, 'Como cadastrar meu pet?', 'Crie uma conta e utilize o menu \"Meu Pet\".', 1, 1),
(2, 'Como marcar consulta?', 'Escolha um veterinário e selecione um horário disponível.', 1, 1),
(3, 'Como comprar produtos?', 'Adicione os itens ao carrinho e finalize o pedido.', 1, 1),
(4, 'Como anunciar minha empresa?', 'Cadastre-se como empresa e envie sua documentação.', 1, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `favoritos`
--

CREATE TABLE `favoritos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `pet_id` int(11) NOT NULL,
  `empresa_id` int(11) DEFAULT NULL,
  `produto_id` int(11) DEFAULT NULL,
  `veterinario_id` int(11) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `favoritos`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `formas_pagamento`
--

CREATE TABLE `formas_pagamento` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `formas_pagamento`
--

INSERT INTO `formas_pagamento` (`id`, `nome`, `descricao`, `ativo`, `criado_em`) VALUES
(1, 'PIX', 'Pagamento instantâneo', 1, '2026-07-29 23:04:44'),
(2, 'Cartão de Crédito', 'Visa, Mastercard, Elo e outros', 1, '2026-07-29 23:04:44'),
(3, 'Cartão de Débito', 'Débito em conta', 1, '2026-07-29 23:04:44'),
(4, 'Boleto Bancário', 'Pagamento via boleto', 1, '2026-07-29 23:04:44'),
(5, 'Dinheiro', 'Pagamento na entrega', 1, '2026-07-29 23:04:44');

-- --------------------------------------------------------

--
-- Estrutura para tabela `fornecedores`
--

CREATE TABLE `fornecedores` (
  `id` int(11) NOT NULL,
  `nome` varchar(180) NOT NULL,
  `cnpj` char(14) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `email` varchar(180) DEFAULT NULL,
  `contato` varchar(150) DEFAULT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `cidade` varchar(120) DEFAULT NULL,
  `estado` char(2) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `historico_medico`
--

CREATE TABLE `historico_medico` (
  `id` int(11) NOT NULL,
  `pet_id` int(11) NOT NULL,
  `veterinario_id` int(11) DEFAULT NULL,
  `data_atendimento` date DEFAULT NULL,
  `diagnostico` text DEFAULT NULL,
  `tratamento` text DEFAULT NULL,
  `medicamentos` text DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `log_acessos`
--

CREATE TABLE `log_acessos` (
  `id` bigint(20) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `navegador` varchar(255) DEFAULT NULL,
  `sistema_operacional` varchar(120) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `data_acesso` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `marcas`
--

CREATE TABLE `marcas` (
  `id` int(11) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `descricao` text DEFAULT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `site` varchar(255) DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `marcas`
--

INSERT INTO `marcas` (`id`, `nome`, `descricao`, `logo`, `site`, `ativo`, `criado_em`) VALUES
(1, 'Royal Canin', NULL, NULL, NULL, 1, '2026-07-29 23:04:43'),
(2, 'Premier Pet', NULL, NULL, NULL, 1, '2026-07-29 23:04:43'),
(3, 'Golden', NULL, NULL, NULL, 1, '2026-07-29 23:04:43'),
(4, 'Purina', NULL, NULL, NULL, 1, '2026-07-29 23:04:43'),
(5, 'Pedigree', NULL, NULL, NULL, 1, '2026-07-29 23:04:43'),
(6, 'Whiskas', NULL, NULL, NULL, 1, '2026-07-29 23:04:43'),
(7, 'GranPlus', NULL, NULL, NULL, 1, '2026-07-29 23:04:43'),
(8, 'Special Dog', NULL, NULL, NULL, 1, '2026-07-29 23:04:43'),
(9, 'Baw Waw', NULL, NULL, NULL, 1, '2026-07-29 23:04:43'),
(10, 'Pet Society', NULL, NULL, NULL, 1, '2026-07-29 23:04:43');

-- --------------------------------------------------------

--
-- Estrutura para tabela `mensagens`
--

CREATE TABLE `mensagens` (
  `id` int(11) NOT NULL,
  `conversa_id` int(11) NOT NULL,
  `remetente_id` int(11) NOT NULL,
  `mensagem` text NOT NULL,
  `lida` tinyint(1) DEFAULT 0,
  `enviado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `mensagens`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `newsletter`
--

CREATE TABLE `newsletter` (
  `id` int(11) NOT NULL,
  `email` varchar(180) NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `newsletter`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `notificacoes`
--

CREATE TABLE `notificacoes` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `titulo` varchar(200) DEFAULT NULL,
  `mensagem` text DEFAULT NULL,
  `link` varchar(255) DEFAULT NULL,
  `tipo` enum('Sistema','Pedido','Pagamento','Consulta','Promoção') DEFAULT 'Sistema',
  `lida` tinyint(1) DEFAULT 0,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `notificacoes`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `pagamentos`
--

CREATE TABLE `pagamentos` (
  `id` int(11) NOT NULL,
  `pedido_id` int(11) NOT NULL,
  `forma_pagamento_id` int(11) NOT NULL,
  `valor` decimal(10,2) NOT NULL,
  `status` enum('Pendente','Aprovado','Recusado','Estornado') DEFAULT 'Pendente',
  `codigo_transacao` varchar(120) DEFAULT NULL,
  `data_pagamento` datetime DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pedidos`
--

CREATE TABLE `pedidos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `numero_pedido` varchar(30) DEFAULT NULL,
  `valor_produtos` decimal(10,2) DEFAULT 0.00,
  `valor_frete` decimal(10,2) DEFAULT 0.00,
  `valor_desconto` decimal(10,2) DEFAULT 0.00,
  `valor_total` decimal(10,2) DEFAULT 0.00,
  `status` enum('Aguardando Pagamento','Pago','Separação','Enviado','Entregue','Cancelado') DEFAULT 'Aguardando Pagamento',
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pedido_itens`
--

CREATE TABLE `pedido_itens` (
  `id` int(11) NOT NULL,
  `pedido_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL,
  `preco_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Acionadores `pedido_itens`
--
DELIMITER $$
CREATE TRIGGER `trg_atualiza_estoque` AFTER INSERT ON `pedido_itens` FOR EACH ROW BEGIN

    UPDATE estoque

    SET quantidade = quantidade - NEW.quantidade

    WHERE produto_id = NEW.produto_id;

END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estrutura para tabela `perfis`
--

CREATE TABLE `perfis` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo` enum('cliente','empresa','veterinario','administrador') DEFAULT 'cliente',
  `biografia` text DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `instagram` varchar(150) DEFAULT NULL,
  `facebook` varchar(150) DEFAULT NULL,
  `youtube` varchar(150) DEFAULT NULL,
  `linkedin` varchar(150) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `perfis`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `pesagens`
--

CREATE TABLE `pesagens` (
  `id` int(11) NOT NULL,
  `pet_id` int(11) NOT NULL,
  `peso` decimal(6,2) NOT NULL,
  `data_pesagem` date NOT NULL,
  `observacoes` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pets`
--

CREATE TABLE `pets` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `especie_id` int(11) NOT NULL,
  `raca_id` int(11) NOT NULL,
  `status` enum('Com Tutor','Perdido','Encontrado','Para Adoção','Adotado') NOT NULL DEFAULT 'Com Tutor',
  `nome` varchar(150) NOT NULL,
  `sexo` enum('Macho','Fêmea') NOT NULL,
  `cor` varchar(100) DEFAULT NULL,
  `peso` decimal(6,2) DEFAULT NULL,
  `altura` decimal(6,2) DEFAULT NULL,
  `data_nascimento` date DEFAULT NULL,
  `microchip` varchar(100) DEFAULT NULL,
  `castrado` tinyint(1) DEFAULT 0,
  `foto` varchar(255) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `pets`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `pets_status_historico`
--

CREATE TABLE `pets_status_historico` (
  `id` int(11) NOT NULL,
  `pet_id` int(11) NOT NULL,
  `status_anterior` varchar(30) DEFAULT NULL,
  `status_novo` varchar(30) NOT NULL,
  `alterado_por` int(11) DEFAULT NULL,
  `motivo` varchar(255) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `pets_status_historico`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `pet_alertas_perdidos`
--

CREATE TABLE `pet_alertas_perdidos` (
  `id` int(11) NOT NULL,
  `pet_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `last_seen_location` varchar(255) NOT NULL,
  `lost_latitude` decimal(10,8) DEFAULT NULL,
  `lost_longitude` decimal(11,8) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` enum('Ativo','Encontrado') DEFAULT 'Ativo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `pet_alertas_perdidos`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `pet_cotutores`
--

CREATE TABLE `pet_cotutores` (
  `id` int(11) NOT NULL,
  `pet_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `convidado_por` int(11) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pet_favoritos`
--

CREATE TABLE `pet_favoritos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `pet_id` int(11) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `pet_favoritos`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `pet_imagens`
--

CREATE TABLE `pet_imagens` (
  `id` int(11) NOT NULL,
  `pet_id` int(11) NOT NULL,
  `arquivo` varchar(255) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `pet_vacinas`
--

CREATE TABLE `pet_vacinas` (
  `id` int(11) NOT NULL,
  `pet_id` int(11) NOT NULL,
  `vacina_id` int(11) NOT NULL,
  `data_aplicacao` date DEFAULT NULL,
  `proxima_dose` date DEFAULT NULL,
  `veterinario` varchar(150) DEFAULT NULL,
  `lote` varchar(100) DEFAULT NULL,
  `observacoes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `produtos`
--

CREATE TABLE `produtos` (
  `id` int(11) NOT NULL,
  `categoria_id` int(11) NOT NULL,
  `subcategoria_id` int(11) DEFAULT NULL,
  `marca_id` int(11) DEFAULT NULL,
  `fornecedor_id` int(11) DEFAULT NULL,
  `empresa_id` int(11) DEFAULT NULL,
  `nome` varchar(200) NOT NULL,
  `descricao` text DEFAULT NULL,
  `sku` varchar(80) DEFAULT NULL,
  `codigo_barras` varchar(50) DEFAULT NULL,
  `peso` decimal(8,2) DEFAULT NULL,
  `altura` decimal(8,2) DEFAULT NULL,
  `largura` decimal(8,2) DEFAULT NULL,
  `comprimento` decimal(8,2) DEFAULT NULL,
  `preco_custo` decimal(10,2) DEFAULT NULL,
  `preco_venda` decimal(10,2) NOT NULL,
  `preco_promocional` decimal(10,2) DEFAULT NULL,
  `destaque` tinyint(1) DEFAULT 0,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `produtos`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `produto_favoritos`
--

CREATE TABLE `produto_favoritos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `produto_imagens`
--

CREATE TABLE `produto_imagens` (
  `id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `imagem` varchar(255) NOT NULL,
  `principal` tinyint(1) DEFAULT 0,
  `ordem` int(11) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `promocoes`
--

CREATE TABLE `promocoes` (
  `id` int(11) NOT NULL,
  `produto_id` int(11) NOT NULL,
  `titulo` varchar(200) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `desconto` decimal(5,2) DEFAULT NULL,
  `data_inicio` date DEFAULT NULL,
  `data_fim` date DEFAULT NULL,
  `ativa` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `prontuarios`
--

CREATE TABLE `prontuarios` (
  `id` int(11) NOT NULL,
  `consulta_id` int(11) NOT NULL,
  `retificacao_de_id` int(11) DEFAULT NULL,
  `organizacao_id` int(11) DEFAULT NULL,
  `diagnostico` text DEFAULT NULL,
  `tratamento` text DEFAULT NULL,
  `medicamentos` text DEFAULT NULL,
  `recomendacoes` text DEFAULT NULL,
  `retorno` date DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `prontuarios`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `racas`
--

CREATE TABLE `racas` (
  `id` int(11) NOT NULL,
  `especie_id` int(11) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `porte` enum('Pequeno','Médio','Grande','Gigante') DEFAULT 'Médio',
  `ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `racas`
--

INSERT INTO `racas` (`id`, `especie_id`, `nome`, `porte`, `ativo`) VALUES
(1, 1, 'Vira-lata (SRD)', 'Médio', 1),
(2, 1, 'Labrador', 'Médio', 1),
(3, 1, 'Poodle', 'Médio', 1),
(4, 1, 'Bulldog Francês', 'Médio', 1),
(5, 1, 'Pastor Alemão', 'Médio', 1),
(6, 1, 'Golden Retriever', 'Médio', 1),
(7, 1, 'Shih Tzu', 'Médio', 1),
(8, 1, 'Chihuahua', 'Médio', 1),
(9, 1, 'Beagle', 'Médio', 1),
(10, 1, 'Rottweiler', 'Médio', 1),
(11, 2, 'Vira-lata (SRD)', 'Médio', 1),
(12, 2, 'Siamês', 'Médio', 1),
(13, 2, 'Persa', 'Médio', 1),
(14, 2, 'Angorá', 'Médio', 1),
(15, 2, 'Maine Coon', 'Médio', 1),
(16, 2, 'Sphynx', 'Médio', 1),
(17, 3, 'Canário', 'Médio', 1),
(18, 3, 'Calopsita', 'Médio', 1),
(19, 3, 'Periquito', 'Médio', 1),
(20, 3, 'Papagaio', 'Médio', 1),
(21, 3, 'Arara', 'Médio', 1),
(22, 4, 'Betta', 'Médio', 1),
(23, 4, 'Kinguio (Goldfish)', 'Médio', 1),
(24, 4, 'Guppy', 'Médio', 1),
(25, 4, 'Tetra', 'Médio', 1),
(26, 4, 'Carpa', 'Médio', 1),
(27, 5, 'Mini Lop', 'Médio', 1),
(28, 5, 'Angorá', 'Médio', 1),
(29, 5, 'Holandês', 'Médio', 1),
(30, 5, 'Rex', 'Médio', 1),
(31, 5, 'Fuzzy Lop', 'Médio', 1),
(32, 6, 'Sírio', 'Médio', 1),
(33, 6, 'Anão Russo', 'Médio', 1),
(34, 6, 'Chinês', 'Médio', 1),
(35, 6, 'Roborovski', 'Médio', 1),
(36, 7, 'Peruano', 'Médio', 1),
(37, 7, 'Americano', 'Médio', 1),
(38, 7, 'Abissínio', 'Médio', 1),
(39, 8, 'Jabuti', 'Médio', 1),
(40, 8, 'Iguana', 'Médio', 1),
(41, 8, 'Gecko', 'Médio', 1),
(42, 8, 'Cágado', 'Médio', 1),
(43, 9, 'Puro Sangue Inglês', 'Médio', 1),
(44, 9, 'Mangalarga', 'Médio', 1),
(45, 9, 'Quarto de Milha', 'Médio', 1),
(46, 9, 'Crioulo', 'Médio', 1),
(47, 10, 'Sem raça definida', 'Médio', 1),
(48, 10, 'Outro', 'Médio', 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `reset_senha_tokens`
--

CREATE TABLE `reset_senha_tokens` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expira_em` datetime NOT NULL,
  `usado` tinyint(1) NOT NULL DEFAULT 0,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `solicitacoes_adocao`
--

CREATE TABLE `solicitacoes_adocao` (
  `id` int(11) NOT NULL,
  `pet_id` int(11) NOT NULL,
  `usuario_solicitante_id` int(11) NOT NULL,
  `conversa_id` int(11) DEFAULT NULL,
  `mensagem` text DEFAULT NULL,
  `status` enum('Pendente','Aprovada','Rejeitada','Cancelada') NOT NULL DEFAULT 'Pendente',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `solicitacoes_adocao`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `subcategorias`
--

CREATE TABLE `subcategorias` (
  `id` int(11) NOT NULL,
  `categoria_id` int(11) NOT NULL,
  `nome` varchar(120) NOT NULL,
  `descricao` text DEFAULT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `subcategorias`
--

INSERT INTO `subcategorias` (`id`, `categoria_id`, `nome`, `descricao`, `ativo`, `criado_em`) VALUES
(1, 9, 'Ração', 'Alimentos secos e úmidos para pets', 1, '2026-08-01 22:41:32'),
(2, 9, 'Brinquedos', 'Brinquedos e itens de entretenimento', 1, '2026-08-01 22:41:32'),
(3, 9, 'Higiene e Beleza', 'Shampoos, perfumes, escovas e produtos de limpeza', 1, '2026-08-01 22:41:32'),
(4, 9, 'Acessórios', 'Coleiras, guias, comedouros e afins', 1, '2026-08-01 22:41:32'),
(5, 9, 'Medicamentos', 'Remédios e suplementos veterinários', 1, '2026-08-01 22:41:32'),
(6, 9, 'Camas e Casinhas', 'Camas, casinhas e itens de conforto', 1, '2026-08-01 22:41:32'),
(7, 9, 'Roupas', 'Roupas e itens de vestuário para pets', 1, '2026-08-01 22:41:32'),
(8, 9, 'Aquários e Terrários', 'Itens para peixes, répteis e outros animais', 1, '2026-08-01 22:41:32');

-- --------------------------------------------------------

--
-- Estrutura para tabela `suporte`
--

CREATE TABLE `suporte` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `assunto` varchar(200) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `prioridade` enum('Baixa','Média','Alta','Urgente') DEFAULT 'Média',
  `status` enum('Aberto','Em Atendimento','Resolvido','Fechado') DEFAULT 'Aberto',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `suporte`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `suporte_respostas`
--

CREATE TABLE `suporte_respostas` (
  `id` int(11) NOT NULL,
  `chamado_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `resposta` text NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `suporte_respostas`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `tentativas_login`
--

CREATE TABLE `tentativas_login` (
  `id` int(11) NOT NULL,
  `email` varchar(180) NOT NULL,
  `tentativas` int(11) NOT NULL DEFAULT 0,
  `ultima_tentativa` timestamp NOT NULL DEFAULT current_timestamp(),
  `bloqueado_ate` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `tentativas_login`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `sobrenome` varchar(150) NOT NULL,
  `email` varchar(180) NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `senha` varchar(255) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `cpf` char(11) DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `data_nascimento` date DEFAULT NULL,
  `genero` enum('Masculino','Feminino','Outro','Prefiro não informar') DEFAULT 'Prefiro não informar',
  `status` enum('ativo','inativo','bloqueado') DEFAULT 'ativo',
  `ultimo_login` datetime DEFAULT NULL,
  `token_recuperacao` varchar(255) DEFAULT NULL,
  `token_expira` datetime DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `tipo_usuario` varchar(20) NOT NULL DEFAULT 'tutor'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `usuarios`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `vacinas`
--

CREATE TABLE `vacinas` (
  `id` int(11) NOT NULL,
  `nome` varchar(150) NOT NULL,
  `descricao` text DEFAULT NULL,
  `periodicidade` varchar(100) DEFAULT NULL,
  `obrigatoria` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `vacinas`
--

INSERT INTO `vacinas` (`id`, `nome`, `descricao`, `periodicidade`, `obrigatoria`) VALUES
(1, 'V10', NULL, NULL, 1),
(2, 'V8', NULL, NULL, 1),
(3, 'Antirrábica', NULL, NULL, 1),
(4, 'Giárdia', NULL, NULL, 0),
(5, 'Gripe Canina', NULL, NULL, 0),
(6, 'Quádrupla Felina', NULL, NULL, 1),
(7, 'Leucemia Felina', NULL, NULL, 0),
(8, 'Raiva Felina', NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Estrutura para tabela `veterinarios`
--

CREATE TABLE `veterinarios` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `crmv` varchar(30) NOT NULL,
  `biografia` text DEFAULT NULL,
  `experiencia` int(11) DEFAULT 0,
  `valor_consulta` decimal(10,2) DEFAULT NULL,
  `atendimento_domicilio` tinyint(1) DEFAULT 0,
  `atendimento_online` tinyint(1) DEFAULT 0,
  `foto` varchar(255) DEFAULT NULL,
  `avaliacao` decimal(2,1) DEFAULT 0.0,
  `total_avaliacoes` int(11) DEFAULT 0,
  `ativo` tinyint(1) DEFAULT 1,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Despejando dados para a tabela `veterinarios`
--


-- --------------------------------------------------------

--
-- Estrutura para tabela `veterinario_especialidades`
--

CREATE TABLE `veterinario_especialidades` (
  `veterinario_id` int(11) NOT NULL,
  `especialidade_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `vw_empresas`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `vw_empresas` (
`id` int(11)
,`nome_fantasia` varchar(180)
,`cidade` varchar(120)
,`estado` char(2)
,`categoria` varchar(120)
,`avaliacao` decimal(2,1)
);

-- --------------------------------------------------------

--
-- Estrutura stand-in para view `vw_produtos`
-- (Veja abaixo para a visão atual)
--
CREATE TABLE `vw_produtos` (
`id` int(11)
,`nome` varchar(200)
,`preco_venda` decimal(10,2)
,`preco_promocional` decimal(10,2)
,`categoria` varchar(120)
,`marca` varchar(150)
);

-- --------------------------------------------------------

--
-- Estrutura para view `vw_empresas`
--
DROP TABLE IF EXISTS `vw_empresas`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_empresas`  AS SELECT `e`.`id` AS `id`, `e`.`nome_fantasia` AS `nome_fantasia`, `e`.`cidade` AS `cidade`, `e`.`estado` AS `estado`, `c`.`nome` AS `categoria`, `e`.`avaliacao` AS `avaliacao` FROM (`empresas` `e` join `categorias` `c` on(`e`.`categoria_id` = `c`.`id`)) ;

-- --------------------------------------------------------

--
-- Estrutura para view `vw_produtos`
--
DROP TABLE IF EXISTS `vw_produtos`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `vw_produtos`  AS SELECT `p`.`id` AS `id`, `p`.`nome` AS `nome`, `p`.`preco_venda` AS `preco_venda`, `p`.`preco_promocional` AS `preco_promocional`, `c`.`nome` AS `categoria`, `m`.`nome` AS `marca` FROM ((`produtos` `p` left join `categorias` `c` on(`p`.`categoria_id` = `c`.`id`)) left join `marcas` `m` on(`p`.`marca_id` = `m`.`id`)) ;

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `agenda_veterinaria`
--
ALTER TABLE `agenda_veterinaria`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_agenda_veterinario` (`veterinario_id`),
  ADD KEY `idx_agenda_data` (`data`);

--
-- Índices de tabela `alergias`
--
ALTER TABLE `alergias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_alergia_pet` (`pet_id`);

--
-- Índices de tabela `auditoria`
--
ALTER TABLE `auditoria`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_auditoria_usuario` (`usuario_id`);

--
-- Índices de tabela `avaliacoes`
--
ALTER TABLE `avaliacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_avaliacao_empresa` (`empresa_id`),
  ADD KEY `fk_avaliacao_produto` (`produto_id`),
  ADD KEY `fk_avaliacao_veterinario` (`veterinario_id`),
  ADD KEY `idx_avaliacoes_usuario` (`usuario_id`);

--
-- Índices de tabela `banners`
--
ALTER TABLE `banners`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_banner_ordem` (`ordem`);

--
-- Índices de tabela `blog_comentarios`
--
ALTER TABLE `blog_comentarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_blog_comentario_usuario` (`usuario_id`);

--
-- Índices de tabela `blog_shares`
--
ALTER TABLE `blog_shares`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_blog_share_usuario` (`usuario_id`);

--
-- Índices de tabela `carrinho`
--
ALTER TABLE `carrinho`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_carrinho_usuario` (`usuario_id`);

--
-- Índices de tabela `carrinho_itens`
--
ALTER TABLE `carrinho_itens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_item_carrinho` (`carrinho_id`),
  ADD KEY `fk_item_produto` (`produto_id`);

--
-- Índices de tabela `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome` (`nome`),
  ADD KEY `idx_categoria_nome` (`nome`);

--
-- Índices de tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `chave_config` (`chave_config`);

--
-- Índices de tabela `consultas`
--
ALTER TABLE `consultas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_consulta_veterinario` (`veterinario_id`),
  ADD KEY `fk_consulta_usuario` (`usuario_id`),
  ADD KEY `idx_consulta_data` (`data_consulta`),
  ADD KEY `idx_consulta_status` (`status`),
  ADD KEY `idx_consulta_empresa` (`empresa_id`);

--
-- Índices de tabela `conversas`
--
ALTER TABLE `conversas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_conversa_origem` (`usuario_origem`),
  ADD KEY `fk_conversa_destino` (`usuario_destino`);

--
-- Índices de tabela `cupons`
--
ALTER TABLE `cupons`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `idx_cupom_codigo` (`codigo`);

--
-- Índices de tabela `cupons_utilizados`
--
ALTER TABLE `cupons_utilizados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_utilizado_cupom` (`cupom_id`),
  ADD KEY `fk_utilizado_pedido` (`pedido_id`),
  ADD KEY `fk_utilizado_usuario` (`usuario_id`);

--
-- Índices de tabela `empresas`
--
ALTER TABLE `empresas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cnpj` (`cnpj`),
  ADD KEY `fk_empresa_usuario` (`usuario_id`),
  ADD KEY `idx_empresa_nome` (`nome_fantasia`),
  ADD KEY `idx_empresa_cidade` (`cidade`),
  ADD KEY `idx_empresa_estado` (`estado`),
  ADD KEY `idx_empresa_categoria` (`categoria_id`);

--
-- Índices de tabela `empresa_equipe`
--
ALTER TABLE `empresa_equipe`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_equipe_empresa_usuario` (`empresa_id`,`usuario_id`),
  ADD KEY `fk_equipe_convidado_por` (`convidado_por`),
  ADD KEY `idx_equipe_usuario` (`usuario_id`);

--
-- Índices de tabela `empresa_galeria`
--
ALTER TABLE `empresa_galeria`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_galeria_empresa` (`empresa_id`);

--
-- Índices de tabela `empresa_horarios`
--
ALTER TABLE `empresa_horarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_horario_empresa` (`empresa_id`);

--
-- Índices de tabela `enderecos`
--
ALTER TABLE `enderecos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `idx_endereco_cidade` (`cidade`),
  ADD KEY `idx_endereco_estado` (`estado`);

--
-- Índices de tabela `especialidades`
--
ALTER TABLE `especialidades`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome` (`nome`);

--
-- Índices de tabela `especies`
--
ALTER TABLE `especies`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome` (`nome`);

--
-- Índices de tabela `estoque`
--
ALTER TABLE `estoque`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_estoque_produto` (`produto_id`);

--
-- Índices de tabela `faq`
--
ALTER TABLE `faq`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `favoritos`
--
ALTER TABLE `favoritos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_favoritos_usuario_pet` (`usuario_id`,`pet_id`),
  ADD KEY `fk_favorito_empresa` (`empresa_id`),
  ADD KEY `fk_favorito_produto` (`produto_id`),
  ADD KEY `fk_favorito_veterinario` (`veterinario_id`),
  ADD KEY `idx_favoritos_usuario` (`usuario_id`);

--
-- Índices de tabela `formas_pagamento`
--
ALTER TABLE `formas_pagamento`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `fornecedores`
--
ALTER TABLE `fornecedores`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `historico_medico`
--
ALTER TABLE `historico_medico`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_historico_veterinario` (`veterinario_id`),
  ADD KEY `idx_historico_pet` (`pet_id`);

--
-- Índices de tabela `log_acessos`
--
ALTER TABLE `log_acessos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_log_usuario` (`usuario_id`);

--
-- Índices de tabela `marcas`
--
ALTER TABLE `marcas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome` (`nome`);

--
-- Índices de tabela `mensagens`
--
ALTER TABLE `mensagens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_mensagem_usuario` (`remetente_id`),
  ADD KEY `idx_mensagens_conversa` (`conversa_id`);

--
-- Índices de tabela `newsletter`
--
ALTER TABLE `newsletter`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_newsletter_email` (`email`);

--
-- Índices de tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notificacoes_usuario` (`usuario_id`);

--
-- Índices de tabela `pagamentos`
--
ALTER TABLE `pagamentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pagamento_pedido` (`pedido_id`),
  ADD KEY `fk_pagamento_forma` (`forma_pagamento_id`),
  ADD KEY `idx_pagamento_status` (`status`);

--
-- Índices de tabela `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_pedido` (`numero_pedido`),
  ADD KEY `idx_pedido_usuario` (`usuario_id`),
  ADD KEY `idx_pedido_status` (`status`);

--
-- Índices de tabela `pedido_itens`
--
ALTER TABLE `pedido_itens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pedido_item` (`pedido_id`),
  ADD KEY `fk_pedido_produto` (`produto_id`);

--
-- Índices de tabela `perfis`
--
ALTER TABLE `perfis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `pesagens`
--
ALTER TABLE `pesagens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_pesagem_pet` (`pet_id`);

--
-- Índices de tabela `pets`
--
ALTER TABLE `pets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pet_especie` (`especie_id`),
  ADD KEY `idx_pet_nome` (`nome`),
  ADD KEY `idx_pet_usuario` (`usuario_id`),
  ADD KEY `idx_pet_raca` (`raca_id`);

--
-- Índices de tabela `pets_status_historico`
--
ALTER TABLE `pets_status_historico`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_status_historico_usuario` (`alterado_por`),
  ADD KEY `idx_status_historico_pet` (`pet_id`);

--
-- Índices de tabela `pet_alertas_perdidos`
--
ALTER TABLE `pet_alertas_perdidos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pet_id` (`pet_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Índices de tabela `pet_cotutores`
--
ALTER TABLE `pet_cotutores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_cotutor_pet_usuario` (`pet_id`,`usuario_id`),
  ADD KEY `fk_cotutor_convidado_por` (`convidado_por`),
  ADD KEY `idx_cotutor_usuario` (`usuario_id`);

--
-- Índices de tabela `pet_favoritos`
--
ALTER TABLE `pet_favoritos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_favorito` (`usuario_id`,`pet_id`),
  ADD KEY `pet_id` (`pet_id`);

--
-- Índices de tabela `pet_imagens`
--
ALTER TABLE `pet_imagens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pet_id` (`pet_id`);

--
-- Índices de tabela `pet_vacinas`
--
ALTER TABLE `pet_vacinas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_petvacina_pet` (`pet_id`),
  ADD KEY `fk_petvacina_vacina` (`vacina_id`);

--
-- Índices de tabela `produtos`
--
ALTER TABLE `produtos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `sku` (`sku`),
  ADD KEY `fk_produto_marca` (`marca_id`),
  ADD KEY `fk_produto_fornecedor` (`fornecedor_id`),
  ADD KEY `idx_produto_nome` (`nome`),
  ADD KEY `idx_produto_categoria` (`categoria_id`),
  ADD KEY `idx_produto_preco` (`preco_venda`),
  ADD KEY `idx_produto_destaque` (`destaque`),
  ADD KEY `fk_produto_empresa` (`empresa_id`),
  ADD KEY `fk_produto_subcategoria` (`subcategoria_id`);

--
-- Índices de tabela `produto_favoritos`
--
ALTER TABLE `produto_favoritos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_prod_fav` (`usuario_id`,`produto_id`),
  ADD KEY `produto_id` (`produto_id`);

--
-- Índices de tabela `produto_imagens`
--
ALTER TABLE `produto_imagens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_imagem_produto` (`produto_id`);

--
-- Índices de tabela `promocoes`
--
ALTER TABLE `promocoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_promocao_produto` (`produto_id`);

--
-- Índices de tabela `prontuarios`
--
ALTER TABLE `prontuarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_prontuario_consulta` (`consulta_id`),
  ADD KEY `fk_prontuario_retificacao` (`retificacao_de_id`);

--
-- Índices de tabela `racas`
--
ALTER TABLE `racas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_raca_especie` (`especie_id`);

--
-- Índices de tabela `reset_senha_tokens`
--
ALTER TABLE `reset_senha_tokens`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_reset_token` (`token`),
  ADD KEY `idx_reset_token_usuario` (`usuario_id`);

--
-- Índices de tabela `solicitacoes_adocao`
--
ALTER TABLE `solicitacoes_adocao`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_solicitacao_conversa` (`conversa_id`),
  ADD KEY `idx_solicitacao_pet` (`pet_id`),
  ADD KEY `idx_solicitacao_usuario` (`usuario_solicitante_id`);

--
-- Índices de tabela `subcategorias`
--
ALTER TABLE `subcategorias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_subcategoria_categoria` (`categoria_id`);

--
-- Índices de tabela `suporte`
--
ALTER TABLE `suporte`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_suporte_usuario` (`usuario_id`),
  ADD KEY `idx_suporte_status` (`status`);

--
-- Índices de tabela `suporte_respostas`
--
ALTER TABLE `suporte_respostas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_resposta_chamado` (`chamado_id`),
  ADD KEY `fk_resposta_usuario` (`usuario_id`);

--
-- Índices de tabela `tentativas_login`
--
ALTER TABLE `tentativas_login`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_email` (`email`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `cpf` (`cpf`),
  ADD KEY `idx_usuario_email` (`email`),
  ADD KEY `idx_usuario_status` (`status`);

--
-- Índices de tabela `vacinas`
--
ALTER TABLE `vacinas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome` (`nome`);

--
-- Índices de tabela `veterinarios`
--
ALTER TABLE `veterinarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `crmv` (`crmv`),
  ADD KEY `fk_veterinario_usuario` (`usuario_id`),
  ADD KEY `idx_veterinario_crmv` (`crmv`),
  ADD KEY `idx_veterinario_avaliacao` (`avaliacao`);

--
-- Índices de tabela `veterinario_especialidades`
--
ALTER TABLE `veterinario_especialidades`
  ADD PRIMARY KEY (`veterinario_id`,`especialidade_id`),
  ADD KEY `fk_ve_especialidade` (`especialidade_id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `agenda_veterinaria`
--
ALTER TABLE `agenda_veterinaria`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `alergias`
--
ALTER TABLE `alergias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `auditoria`
--
ALTER TABLE `auditoria`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `avaliacoes`
--
ALTER TABLE `avaliacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `banners`
--
ALTER TABLE `banners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `blog_comentarios`
--
ALTER TABLE `blog_comentarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `blog_shares`
--
ALTER TABLE `blog_shares`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `carrinho`
--
ALTER TABLE `carrinho`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `carrinho_itens`
--
ALTER TABLE `carrinho_itens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `consultas`
--
ALTER TABLE `consultas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `conversas`
--
ALTER TABLE `conversas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `cupons`
--
ALTER TABLE `cupons`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `cupons_utilizados`
--
ALTER TABLE `cupons_utilizados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `empresas`
--
ALTER TABLE `empresas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `empresa_equipe`
--
ALTER TABLE `empresa_equipe`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `empresa_galeria`
--
ALTER TABLE `empresa_galeria`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `empresa_horarios`
--
ALTER TABLE `empresa_horarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `enderecos`
--
ALTER TABLE `enderecos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `especialidades`
--
ALTER TABLE `especialidades`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de tabela `especies`
--
ALTER TABLE `especies`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `estoque`
--
ALTER TABLE `estoque`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `faq`
--
ALTER TABLE `faq`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `favoritos`
--
ALTER TABLE `favoritos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `formas_pagamento`
--
ALTER TABLE `formas_pagamento`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `fornecedores`
--
ALTER TABLE `fornecedores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `historico_medico`
--
ALTER TABLE `historico_medico`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `log_acessos`
--
ALTER TABLE `log_acessos`
  MODIFY `id` bigint(20) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `marcas`
--
ALTER TABLE `marcas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `mensagens`
--
ALTER TABLE `mensagens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `newsletter`
--
ALTER TABLE `newsletter`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `pagamentos`
--
ALTER TABLE `pagamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `pedido_itens`
--
ALTER TABLE `pedido_itens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `perfis`
--
ALTER TABLE `perfis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `pesagens`
--
ALTER TABLE `pesagens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `pets`
--
ALTER TABLE `pets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `pets_status_historico`
--
ALTER TABLE `pets_status_historico`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `pet_alertas_perdidos`
--
ALTER TABLE `pet_alertas_perdidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `pet_cotutores`
--
ALTER TABLE `pet_cotutores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `pet_favoritos`
--
ALTER TABLE `pet_favoritos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `pet_imagens`
--
ALTER TABLE `pet_imagens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `pet_vacinas`
--
ALTER TABLE `pet_vacinas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `produtos`
--
ALTER TABLE `produtos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `produto_favoritos`
--
ALTER TABLE `produto_favoritos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `produto_imagens`
--
ALTER TABLE `produto_imagens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `promocoes`
--
ALTER TABLE `promocoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `prontuarios`
--
ALTER TABLE `prontuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `racas`
--
ALTER TABLE `racas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=49;

--
-- AUTO_INCREMENT de tabela `reset_senha_tokens`
--
ALTER TABLE `reset_senha_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `solicitacoes_adocao`
--
ALTER TABLE `solicitacoes_adocao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `subcategorias`
--
ALTER TABLE `subcategorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `suporte`
--
ALTER TABLE `suporte`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `suporte_respostas`
--
ALTER TABLE `suporte_respostas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `tentativas_login`
--
ALTER TABLE `tentativas_login`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- AUTO_INCREMENT de tabela `vacinas`
--
ALTER TABLE `vacinas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `veterinarios`
--
ALTER TABLE `veterinarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `agenda_veterinaria`
--
ALTER TABLE `agenda_veterinaria`
  ADD CONSTRAINT `fk_agenda_veterinario` FOREIGN KEY (`veterinario_id`) REFERENCES `veterinarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `alergias`
--
ALTER TABLE `alergias`
  ADD CONSTRAINT `fk_alergia_pet` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `auditoria`
--
ALTER TABLE `auditoria`
  ADD CONSTRAINT `fk_auditoria_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `avaliacoes`
--
ALTER TABLE `avaliacoes`
  ADD CONSTRAINT `fk_avaliacao_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_avaliacao_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_avaliacao_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `fk_avaliacao_veterinario` FOREIGN KEY (`veterinario_id`) REFERENCES `veterinarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `blog_comentarios`
--
ALTER TABLE `blog_comentarios`
  ADD CONSTRAINT `fk_blog_comentario_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `blog_shares`
--
ALTER TABLE `blog_shares`
  ADD CONSTRAINT `fk_blog_share_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `carrinho`
--
ALTER TABLE `carrinho`
  ADD CONSTRAINT `fk_carrinho_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `carrinho_itens`
--
ALTER TABLE `carrinho_itens`
  ADD CONSTRAINT `fk_item_carrinho` FOREIGN KEY (`carrinho_id`) REFERENCES `carrinho` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_item_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`);

--
-- Restrições para tabelas `consultas`
--
ALTER TABLE `consultas`
  ADD CONSTRAINT `fk_consulta_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_consulta_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `fk_consulta_veterinario` FOREIGN KEY (`veterinario_id`) REFERENCES `veterinarios` (`id`);

--
-- Restrições para tabelas `conversas`
--
ALTER TABLE `conversas`
  ADD CONSTRAINT `fk_conversa_destino` FOREIGN KEY (`usuario_destino`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `fk_conversa_origem` FOREIGN KEY (`usuario_origem`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `cupons_utilizados`
--
ALTER TABLE `cupons_utilizados`
  ADD CONSTRAINT `fk_utilizado_cupom` FOREIGN KEY (`cupom_id`) REFERENCES `cupons` (`id`),
  ADD CONSTRAINT `fk_utilizado_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`),
  ADD CONSTRAINT `fk_utilizado_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `empresas`
--
ALTER TABLE `empresas`
  ADD CONSTRAINT `fk_empresa_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`),
  ADD CONSTRAINT `fk_empresa_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `empresa_equipe`
--
ALTER TABLE `empresa_equipe`
  ADD CONSTRAINT `fk_equipe_convidado_por` FOREIGN KEY (`convidado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_equipe_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_equipe_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `empresa_galeria`
--
ALTER TABLE `empresa_galeria`
  ADD CONSTRAINT `fk_galeria_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `empresa_horarios`
--
ALTER TABLE `empresa_horarios`
  ADD CONSTRAINT `fk_horario_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `enderecos`
--
ALTER TABLE `enderecos`
  ADD CONSTRAINT `enderecos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `estoque`
--
ALTER TABLE `estoque`
  ADD CONSTRAINT `fk_estoque_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `favoritos`
--
ALTER TABLE `favoritos`
  ADD CONSTRAINT `fk_favorito_empresa` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_favorito_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_favorito_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_favorito_veterinario` FOREIGN KEY (`veterinario_id`) REFERENCES `veterinarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `historico_medico`
--
ALTER TABLE `historico_medico`
  ADD CONSTRAINT `fk_historico_pet` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_historico_veterinario` FOREIGN KEY (`veterinario_id`) REFERENCES `veterinarios` (`id`);

--
-- Restrições para tabelas `log_acessos`
--
ALTER TABLE `log_acessos`
  ADD CONSTRAINT `fk_log_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `mensagens`
--
ALTER TABLE `mensagens`
  ADD CONSTRAINT `fk_mensagem_conversa` FOREIGN KEY (`conversa_id`) REFERENCES `conversas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_mensagem_usuario` FOREIGN KEY (`remetente_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `notificacoes`
--
ALTER TABLE `notificacoes`
  ADD CONSTRAINT `fk_notificacao_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `pagamentos`
--
ALTER TABLE `pagamentos`
  ADD CONSTRAINT `fk_pagamento_forma` FOREIGN KEY (`forma_pagamento_id`) REFERENCES `formas_pagamento` (`id`),
  ADD CONSTRAINT `fk_pagamento_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `fk_pedido_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `pedido_itens`
--
ALTER TABLE `pedido_itens`
  ADD CONSTRAINT `fk_pedido_item` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pedido_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`);

--
-- Restrições para tabelas `perfis`
--
ALTER TABLE `perfis`
  ADD CONSTRAINT `perfis_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `pesagens`
--
ALTER TABLE `pesagens`
  ADD CONSTRAINT `fk_pesagem_pet` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `pets`
--
ALTER TABLE `pets`
  ADD CONSTRAINT `fk_pet_especie` FOREIGN KEY (`especie_id`) REFERENCES `especies` (`id`),
  ADD CONSTRAINT `fk_pet_raca` FOREIGN KEY (`raca_id`) REFERENCES `racas` (`id`),
  ADD CONSTRAINT `fk_pet_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `pets_status_historico`
--
ALTER TABLE `pets_status_historico`
  ADD CONSTRAINT `fk_status_historico_pet` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_status_historico_usuario` FOREIGN KEY (`alterado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `pet_cotutores`
--
ALTER TABLE `pet_cotutores`
  ADD CONSTRAINT `fk_cotutor_convidado_por` FOREIGN KEY (`convidado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_cotutor_pet` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_cotutor_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `pet_favoritos`
--
ALTER TABLE `pet_favoritos`
  ADD CONSTRAINT `pet_favoritos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pet_favoritos_ibfk_2` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `pet_imagens`
--
ALTER TABLE `pet_imagens`
  ADD CONSTRAINT `pet_imagens_ibfk_1` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `pet_vacinas`
--
ALTER TABLE `pet_vacinas`
  ADD CONSTRAINT `fk_petvacina_pet` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_petvacina_vacina` FOREIGN KEY (`vacina_id`) REFERENCES `vacinas` (`id`);

--
-- Restrições para tabelas `produtos`
--
ALTER TABLE `produtos`
  ADD CONSTRAINT `fk_produto_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`),
  ADD CONSTRAINT `fk_produto_fornecedor` FOREIGN KEY (`fornecedor_id`) REFERENCES `fornecedores` (`id`),
  ADD CONSTRAINT `fk_produto_marca` FOREIGN KEY (`marca_id`) REFERENCES `marcas` (`id`);

--
-- Restrições para tabelas `produto_favoritos`
--
ALTER TABLE `produto_favoritos`
  ADD CONSTRAINT `produto_favoritos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `produto_favoritos_ibfk_2` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `produto_imagens`
--
ALTER TABLE `produto_imagens`
  ADD CONSTRAINT `fk_imagem_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `promocoes`
--
ALTER TABLE `promocoes`
  ADD CONSTRAINT `fk_promocao_produto` FOREIGN KEY (`produto_id`) REFERENCES `produtos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `prontuarios`
--
ALTER TABLE `prontuarios`
  ADD CONSTRAINT `fk_prontuario_consulta` FOREIGN KEY (`consulta_id`) REFERENCES `consultas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_prontuario_retificacao` FOREIGN KEY (`retificacao_de_id`) REFERENCES `prontuarios` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `racas`
--
ALTER TABLE `racas`
  ADD CONSTRAINT `fk_raca_especie` FOREIGN KEY (`especie_id`) REFERENCES `especies` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `reset_senha_tokens`
--
ALTER TABLE `reset_senha_tokens`
  ADD CONSTRAINT `fk_reset_token_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `solicitacoes_adocao`
--
ALTER TABLE `solicitacoes_adocao`
  ADD CONSTRAINT `fk_solicitacao_conversa` FOREIGN KEY (`conversa_id`) REFERENCES `conversas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_solicitacao_pet` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_solicitacao_usuario` FOREIGN KEY (`usuario_solicitante_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `subcategorias`
--
ALTER TABLE `subcategorias`
  ADD CONSTRAINT `fk_subcategoria_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `suporte`
--
ALTER TABLE `suporte`
  ADD CONSTRAINT `fk_suporte_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `suporte_respostas`
--
ALTER TABLE `suporte_respostas`
  ADD CONSTRAINT `fk_resposta_chamado` FOREIGN KEY (`chamado_id`) REFERENCES `suporte` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_resposta_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `veterinarios`
--
ALTER TABLE `veterinarios`
  ADD CONSTRAINT `fk_veterinario_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `veterinario_especialidades`
--
ALTER TABLE `veterinario_especialidades`
  ADD CONSTRAINT `fk_ve_especialidade` FOREIGN KEY (`especialidade_id`) REFERENCES `especialidades` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_ve_veterinario` FOREIGN KEY (`veterinario_id`) REFERENCES `veterinarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

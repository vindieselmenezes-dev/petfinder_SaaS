-- Opção "mostrar meu telefone neste alerta" (pets ENCONTRADOS publicados pelo Alerta Pet).
-- Desligada por padrão: o telefone só aparece se a pessoa ligar a opção.
-- Rode uma vez no phpMyAdmin (banco do site).
ALTER TABLE pets
    ADD COLUMN mostrar_telefone TINYINT(1) NOT NULL DEFAULT 0 AFTER observacoes;

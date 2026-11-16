/*
   MIGRACAO 033 - Clinicas parceiras registram vacinas

   Guarda quem registrou cada dose: a empresa (clinica) e o usuario da
   equipe. Doses lancadas pelo proprio tutor ficam com os dois nulos.
*/

USE petfinder;

ALTER TABLE pet_vacinas
    ADD COLUMN empresa_id INT NULL AFTER observacoes,
    ADD COLUMN registrado_por INT NULL AFTER empresa_id,
    ADD INDEX idx_pet_vacinas_empresa (empresa_id);

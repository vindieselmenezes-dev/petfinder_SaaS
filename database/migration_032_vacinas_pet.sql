/*
   MIGRACAO 032 - Quadro de vacinas do pet

   A tabela pet_vacinas ja existia no schema original, mas nenhuma
   tela usava. Esta migracao so acrescenta a periodicidade em dias
   no catalogo (para sugerir a proxima dose) e um indice de consulta.
*/

USE petfinder;

ALTER TABLE vacinas
    ADD COLUMN periodicidade_dias INT NULL AFTER periodicidade;

UPDATE vacinas SET periodicidade_dias = 365 WHERE periodicidade_dias IS NULL;
UPDATE vacinas SET periodicidade = 'Anual' WHERE periodicidade IS NULL;

ALTER TABLE pet_vacinas
    ADD INDEX idx_pet_vacinas_pet_proxima (pet_id, proxima_dose);

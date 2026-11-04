-- Permite cadastrar um pet ENCONTRADO sem saber o sexo (Alerta Pet rápido da home).
-- Rode uma vez no phpMyAdmin (banco do site) antes de usar o "Achei um pet".
ALTER TABLE pets
    MODIFY COLUMN sexo ENUM('Macho','Fêmea','Indefinido') NOT NULL;

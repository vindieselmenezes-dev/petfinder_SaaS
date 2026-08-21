/* ============================================================
   PETFINDER BRASIL
   MIGRAÇÃO 004 — Coordenadas opcionais no alerta de pet perdido

   Antes, lost_latitude/lost_longitude eram NOT NULL e o
   formulário mandava uma coordenada FALSA e fixa quando não
   tinha a real. Agora a localização vem de verdade do GPS do
   navegador (ver assets/js/geolocalizacao.js), e a pessoa pode
   negar a permissão — nesse caso, o alerta ainda deve poder
   ser publicado, só sem o aviso automático por proximidade.
   ============================================================ */

USE petfinder;

ALTER TABLE pet_alertas_perdidos
    MODIFY COLUMN lost_latitude DECIMAL(10,8) NULL,
    MODIFY COLUMN lost_longitude DECIMAL(11,8) NULL;

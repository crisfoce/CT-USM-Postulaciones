USE ct_usm_postulaciones;

DROP TRIGGER IF EXISTS trg_plazo_etapa;

DELIMITER //

CREATE TRIGGER trg_plazo_etapa
BEFORE INSERT ON etapa_cronograma
FOR EACH ROW
BEGIN
    IF NEW.plazo_semanas<=0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT= 'El plazo de la etapa debe ser mayor a 0 semanas.';
    END IF;
END //

DELIMITER ;

/*
Prueba valida:
INSERT INTO etapa_cronograma(id_postulacion, nombre_etapa, plazo_semanas, entregable)
VALUES (1, 'Revision final', 2, 'Informe de revision final');

Prueba invalida:
INSERT INTO etapa_cronograma(id_postulacion, nombre_etapa, plazo_semanas, entregable)
VALUES (1, 'Revision final', 0, 'Informe de revision final');
*/
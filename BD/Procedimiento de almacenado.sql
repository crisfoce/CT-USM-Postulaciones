USE ct_usm_postulaciones;

DROP PROCEDURE IF EXISTS sp_agregar_documento;

DELIMITER //

CREATE PROCEDURE sp_agregar_documento(IN p_id_postulacion INT, IN p_otros_documentos VARCHAR(255))

BEGIN
    INSERT INTO documento(id_postulacion, otros_documentos) VALUES(p_id_postulacion, p_otros_documentos);
END //

DELIMITER ;

/*
Para probarlo:
CALL sp_agregar_documento(1, 'Carta de compromiso de la empresa');

Luego:
SELECT *
FROM documento
WHERE id_postulacion = 1;
*/
-- Ajustes de índices caso as tabelas já existam
ALTER TABLE reservas_tematicas_config
    ADD UNIQUE KEY uq_res_tem_config_rest (restaurante_id);

ALTER TABLE reservas_tematicas_config_turnos
    ADD UNIQUE KEY uq_res_tem_cfg_turno (restaurante_id, turno_id);

ALTER TABLE reservas_tematicas_fechamentos
    ADD UNIQUE KEY uq_res_tem_fech (restaurante_id, data_reserva, turno_id);

ALTER TABLE reservas_tematicas
    ADD INDEX idx_res_tem_data_rest_turno (data_reserva, restaurante_id, turno_id),
    ADD INDEX idx_res_tem_uh (uh_id);

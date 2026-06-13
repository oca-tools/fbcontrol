ALTER TABLE acessos
    ADD INDEX IF NOT EXISTS idx_acessos_rest_oper_data (restaurante_id, operacao_id, criado_em),
    ADD INDEX IF NOT EXISTS idx_acessos_duplicate_scan (uh_id, operacao_id, criado_em, pax);

ALTER TABLE reservas_tematicas
    ADD INDEX IF NOT EXISTS idx_res_tem_data_id (data_reserva, id),
    ADD INDEX IF NOT EXISTS idx_res_tem_rest_data_id (restaurante_id, data_reserva, id),
    ADD INDEX IF NOT EXISTS idx_res_tem_duplicate_lookup (uh_id, data_reserva, turno_id, restaurante_id, status);

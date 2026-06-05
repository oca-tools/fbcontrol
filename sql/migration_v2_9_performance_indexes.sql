ALTER TABLE acessos_especiais
    ADD INDEX IF NOT EXISTS idx_acessos_esp_rest_tipo_data (restaurante_id, tipo, criado_em),
    ADD INDEX IF NOT EXISTS idx_acessos_esp_user_data (usuario_id, criado_em);

ALTER TABLE auditoria
    ADD INDEX IF NOT EXISTS idx_auditoria_data (criado_em, id),
    ADD INDEX IF NOT EXISTS idx_auditoria_tabela_data (tabela, criado_em),
    ADD INDEX IF NOT EXISTS idx_auditoria_user_data (usuario_id, criado_em);

ALTER TABLE colaborador_refeicoes
    ADD INDEX IF NOT EXISTS idx_colab_data (criado_em),
    ADD INDEX IF NOT EXISTS idx_colab_rest_oper_data (restaurante_id, operacao_id, criado_em),
    ADD INDEX IF NOT EXISTS idx_colab_user_data (usuario_id, criado_em);

ALTER TABLE reservas_tematicas_logs
    ADD INDEX IF NOT EXISTS idx_res_tem_logs_data (criado_em),
    ADD INDEX IF NOT EXISTS idx_res_tem_logs_user_data (usuario_id, criado_em),
    ADD INDEX IF NOT EXISTS idx_res_tem_logs_reserva_data (reserva_id, criado_em);

ALTER TABLE turnos
    ADD INDEX IF NOT EXISTS idx_turnos_inicio (inicio_em),
    ADD INDEX IF NOT EXISTS idx_turnos_user_fim_inicio (usuario_id, fim_em, inicio_em),
    ADD INDEX IF NOT EXISTS idx_turnos_rest_oper_inicio (restaurante_id, operacao_id, inicio_em);

ALTER TABLE turnos_especiais
    ADD INDEX IF NOT EXISTS idx_turnos_esp_inicio (inicio_em),
    ADD INDEX IF NOT EXISTS idx_turnos_esp_user_fim_inicio (usuario_id, fim_em, inicio_em),
    ADD INDEX IF NOT EXISTS idx_turnos_esp_rest_tipo_inicio (restaurante_id, tipo, inicio_em);

ALTER TABLE vouchers
    ADD INDEX IF NOT EXISTS idx_vouchers_data (criado_em),
    ADD INDEX IF NOT EXISTS idx_vouchers_rest_oper_data (restaurante_id, operacao_id, criado_em),
    ADD INDEX IF NOT EXISTS idx_vouchers_user_data (usuario_id, criado_em),
    ADD INDEX IF NOT EXISTS idx_vouchers_data_venda (data_venda);

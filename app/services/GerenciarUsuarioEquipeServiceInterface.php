<?php
declare(strict_types=1);

interface GerenciarUsuarioEquipeServiceInterface
{
    /**
     * Registra ou atualiza colaboradores da equipe de A&B e vincula permissoes operacionais.
     */
    public function executar(GerenciarUsuarioEquipeCommand $command): ServiceResult;
}

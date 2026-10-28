<?php

require_once __DIR__ . '/../models/PlanDiarioModel.php';

class PlanDiarioController
{
    private PlanDiarioModel $planDiario;

    public function __construct()
    {
        $this->planDiario = new PlanDiarioModel();
    }

    public function obtenerPlanDiarioActivo(Request $request)
    {
        return $this->planDiario->listActivePlans();
    }
}

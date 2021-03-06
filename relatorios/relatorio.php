<?php
/**
 * Controle de Ponto Eletronico
 *
 * PHP version >= 5.2
 *
 * @category  Ponto
 * @package   Importacao
 * @author    Wolmer Willian Andrill Pinto <wolmerwap@gmail.com>
 * @copyright 2003-2012 Com�rcio e Servi�os Ltda.
 * @link      http://www.wolmerwap.com.br
 */

include '../default/default.php';
include __path_classes__ . '/genericas/datahora.class.php';

/**
 * Instaciando as classes
 */
$db = new DB();
$dh = new DATAHORA();

/*
 * Busca o usu�rio
 */
$db->execScript('SELECT id FROM funcionarios WHERE usuario = "wolmer"');
if ($db->num_linhas) {
    $usuario = $db->dados[0]['id'];
} else {
    exit('O funcion�rio n�o foi encontrado para listar os hor�rios.');
}

/*
 * Busca a quantidade de horas padrao do funcionario
 */
$db->execScript("
        SELECT 
            TIMEDIFF(saida1, entrada1) as diff1, 
            TIMEDIFF(saida2, entrada2) as diff2
        FROM horarios_padrao WHERE id_funcionario = {$usuario}
    ");
$hora_padrao = $dh->somar_hora($db->dados[0]['diff1'], $db->dados[0]['diff2']);

/*
 * Busca as horas do funcionario
 */
$db->execScript("
        SELECT data, entrada, saida, TIMEDIFF(saida, entrada) as diferenca
        FROM controle_horarios WHERE id_funcionario = {$usuario}
        ORDER BY data, entrada
    ");
        
$j = 0;
$k = 1;
$horas = array();
$entradas = 1;
for ($i = 0; $i < count($db->dados); $i++) {
    $dados = $db->dados[$i];
    $horas[$j]['data'] = str_replace('-', '/', $dados['data']);
    $horas[$j]['entrada_' . $k] = $dados['entrada'];
    $horas[$j]['saida_' . $k] = $dados['saida'];
    
    if ($k > 1) {
        $horas[$j]['diferenca'] = $dh->somar_hora(
                $horas[$j]['diferenca'], $dados['diferenca']
            );
    } else {
        $horas[$j]['diferenca'] = $dados['diferenca'];
    }
    $horas[$j]['saldo'] = $dh->subtrair_hora($horas[$j]['diferenca'], $hora_padrao);
    
    if ($db->dados[$i + 1]['data'] != $dados['data']) {
        if (isset ($horas[$j-1]['banco'])) {
            $horas[$j]['banco'] = $dh->somar_hora(
                    $horas[$j]['saldo'], $horas[$j-1]['banco']
                );
        } else {
            $horas[$j]['banco'] = $horas[$j]['saldo'];
        }
        
        $j++;
        $k = 1;
    } else {
        $k++;
        if ($k > $entradas) {
            $entradas = $k;
        }
    }

}
?>

<html>
    <head>
        <script type="text/javascript" language="javascript" src="../includes/js/jquery.js"></script>
		<script type="text/javascript" language="javascript" src="../includes/js/jquery.dataTables.js"></script>
        <style type="text/css" title="currentStyle">
			@import "../includes/css/table.css";
		</style> 
        <script type="text/javascript" charset="utf-8">
			$(document).ready(function() {
				$('#example').dataTable();
			} );
		</script>
    </head>
    <body style="text-align: center; float: center;">
        <div style="width: 400px; margin-left: 40%; margin-right: 40%;">
            <table id="example" style="float: center; text-align: center;"> 
                <thead> 
                    <tr> 
                        <th>Data</th> 
                        <?php for ($i = 0; $i < $entradas; $i++) { ?>
                        <th>Entrada</th> 
                        <th>Sa�da</th> 
                        <?php } ?>
                        <th>Total</th> 
                        <th>Saldo</th> 
                        <th>Banco</th> 
                    </tr> 
                </thead> 
                <tbody> 
                    <?php 
                    foreach ($horas as $dados) { 
                    ?>
                    <tr> 
                        <td><?php echo $dados['data'];?></td> 
                        <?php for ($i = 1; $i <= $entradas; $i++) { ?>
                        <td><?php echo $dados['entrada_' . $i];?></td> 
                        <td><?php echo $dados['saida_' . $i];?></td> 
                        <?php } ?>
                        <td><?php echo $dados['diferenca'];?></td> 
                        <td><?php echo $dados['saldo'];?></td> 
                        <td><?php echo $dados['banco'];?></td> 
                    </tr> 
                    <?php } ?> 
                </tbody> 
            </table>
        </div>
    </body>
</html>


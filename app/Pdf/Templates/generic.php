<?php

/**
 * @noinspection PhpFullyQualifiedNameUsageInspection
 */

declare(strict_types=1);

/**
 * @var \League\Plates\Template\Template $this
 * @var \PhpCfdi\CfdiToPdf\CfdiData $cfdiData
 * @var \PhpCfdi\CfdiToPdf\Catalogs\CatalogsInterface|null $catalogos
 */
$comprobante = $cfdiData->comprobante();
$emisor = $cfdiData->emisor();
$receptor = $cfdiData->receptor();
$tfd = $cfdiData->timbreFiscalDigital();
$relacionados = $comprobante->searchNodes('cfdi:CfdiRelacionados');
$totalImpuestosTrasladados = $comprobante->searchAttribute('cfdi:Impuestos', 'TotalImpuestosTrasladados');
$totalImpuestosRetenidos = $comprobante->searchAttribute('cfdi:Impuestos', 'TotalImpuestosRetenidos');
$conceptos = $comprobante->searchNodes('cfdi:Conceptos', 'cfdi:Concepto');
$informacionGlobal = $comprobante->searchNode('cfdi:InformacionGlobal');
$conceptoCounter = 0;
$conceptoCount = $conceptos->count();
if (! isset($catalogos) || ! ($catalogos instanceof \PhpCfdi\CfdiToPdf\Catalogs\CatalogsInterface)) {
    $catalogos = new \PhpCfdi\CfdiToPdf\Catalogs\StaticCatalogs();
}
?>
<style>
    * {
        font-size: 8pt;
        padding: 0;
        margin: 0;
    }

    table th,
    table td {
        vertical-align: top;
        text-align: center;
    }

    table th {
        font-weight: bold;
    }

    table.tabular th {
        text-align: right;
    }

    table.tabular td {
        text-align: left;
    }

    div.panel {
        border: 0.2mm solid #0000;
        margin-bottom: 1mm;
    }

    div.panel div.title {
        background-color: #3084f2;
        color: #ffffff;
        font-weight: bold;
        padding: 1mm 2mm;
    }

    div.panel div.content {
        padding: 1mm 2mm;
    }

    .main-table {
        border-collapse: collapse;
        width: 100%;
    }

    .main-table th {
        background-color: #3084f2;
        color: #ffffff;
        border: 0px solid;
        padding: 2px;
        text-align: center;
    }

    .main-table td {
        padding: 4px;
    }

    .total-left {
        text-align: left;
        background-color: #102540;
        padding: 4px;
        color: whitesmoke;
    }

    .total-right {
        text-align: right;
        background-color: #F2F2F2;
        padding: 4px;
        border-bottom: 1px;
    }
</style>
<!--suppress HtmlUnknownTag -->
<page backbottom="10mm">
    <page_footer>
        <p style="text-align: center">
            Este documento es una representación impresa de un Comprobante Fiscal Digital a través de Internet
            versión <?= $this->e($comprobante['Version']) ?>
            <br />UUID: <?= $this->e($tfd['UUID']) ?> - Página [[page_cu]] de [[page_nb]]
        </p>
    </page_footer>


    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="width: 100px; vertical-align: top;">
                <?php if ($src) : ?>
                    <img src="<?= $src ?>" alt="Daventas Organization Logo" style="width: 100px; height: 100px;">
                <?php endif; ?>
            </td>
            <td style="vertical-align: top; padding-left: 10px;width:40%; text-align: left;">
                <p>
                    <strong><?= $this->e($emisor['Nombre'] ?: 'No se especificó el nombre del emisor') ?></strong>
                    <br />RFC: <?= $this->e($emisor['Rfc']) ?>
                    <br />Régimen fiscal: <?= $catalogos->catRegimenFiscal($emisor['RegimenFiscal']) ?>
                </p>
            </td>
            <td style="vertical-align: top; padding-left: 10px; width:40%;">
                <p>
                    Tipo:
                    <?= $catalogos->catTipoComprobante($comprobante['TipoDeComprobante']) ?><br>
                    Serie:
                    <?= $this->e($comprobante['Serie']) ?><br>
                    Folio:
                    <?= $this->e($comprobante['Folio']) ?><br>
                    Lugar de expedición:
                    <?= $this->e($comprobante['LugarExpedicion']) ?><br>
                    Fecha:
                    <?= $this->e($comprobante['Fecha']) ?><br>
                    Forma de pago:
                    <?= $catalogos->catFormaPago($comprobante['FormaPago']) ?><br>
                    Método de pago:
                    <?= $catalogos->catMetodoPago($comprobante['MetodoPago']) ?><br>
                    <?php if ('' !== $comprobante['CondicionesDePago']) : ?>

                        Condiciones de pago:
                        <?= $this->e($comprobante['CondicionesDePago']) ?><br>
                    <?php endif; ?>
                </p>
            </td>
        </tr>

    </table>
    <div>
        <table style="width: 100%; border-collapse: collapse; margin-top: 20px; margin-bottom: 20px;">
            <tr>
                <td style="vertical-align: top; text-align: left; width:40%;">
                    <strong>
                        <p>Datos del cliente</p>
                    </strong>
                    <p>
                        <?= $this->e($receptor['Nombre'] ?: '(No se especificó el nombre del receptor)') ?>
                        <br />RFC: <?= $this->e($receptor['Rfc']) ?>
                        <br />Uso del CFDI: <?= $catalogos->catUsoCFDI($receptor['UsoCFDI']) ?>
                        <?php if ('' !== $receptor['DomicilioFiscalReceptor']) : ?>
                            <br />Domicilio fiscal receptor: <?= $this->e($receptor['DomicilioFiscalReceptor']) ?>
                        <?php endif; ?>
                        <?php if ('' !== $receptor['RegimenFiscalReceptor']) : ?>
                            <br />Régimen fiscal receptor: <?= $catalogos->catRegimenFiscal($receptor['RegimenFiscalReceptor']) ?>
                        <?php endif; ?>
                        <?php if ('' !== $receptor['ResidenciaFiscal']) : ?>
                            <br />Residencia fiscal: <?= $this->e($receptor['ResidenciaFiscal']) ?>
                        <?php endif; ?>
                        <?php if ('' !== $receptor['NumRegIdTrib']) : ?>
                            <br />Residencia fiscal: <?= $this->e($receptor['NumRegIdTrib']) ?>
                        <?php endif; ?>
                    </p>
                </td>
                <td style="vertical-align: top; padding-left: 10px; width:40%;">
                    <?php if (null !== $informacionGlobal) : ?>
                        <strong>
                            <p>Información global</p>
                        </strong>
                        <p>
                            Periodicidad: <?= $catalogos->catPeriodicidad($informacionGlobal['Periodicidad']) ?>
                            <br />Meses: <?= $catalogos->catMeses($informacionGlobal['Meses']) ?>
                            <br />Año: <?= $this->e($informacionGlobal['Año']) ?>
                        </p>
                    <?php endif; ?>
                </td>
            </tr>

        </table>

    </div>
    <?php foreach ($relacionados as $relacionado) : ?>
        <div class="panel">
            <div class="title">CFDI Relacionados (Tipo de relación: <?= $this->e($relacionado['TipoRelacion']) ?>)</div>
            <div class="content">
                <?php foreach ($relacionado->searchNodes('cfdi:CfdiRelacionado') as $cfdiRelacionado) : ?>
                    <span>UUID: <?= $cfdiRelacionado['UUID'] ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <div>
        <table class="main-table" style>
            <thead>
                <tr>
                    <th style="width: 10%">Cant. y unidad</th>
                    <th style="width: 10%">Clave SAT</th>
                    <th style="width: 29%">Descripción</th>
                    <th style="width: 10%">Obj. Imp.</th>
                    <th style="width: 10%">Precio unitario</th>
                    <th style="width: 10%">Descuento</th>
                    <th style="width: 10%">Impuestos</th>
                    <th style="width: 10%">Importe</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($conceptos as $concepto) : ?>
                    <?php
                    $conceptoCounter++;
                    $conceptoTraslados = $concepto->searchNodes('cfdi:Impuestos', 'cfdi:Traslados', 'cfdi:Traslado');
                    $conceptoRetenciones = $concepto->searchNodes('cfdi:Impuestos', 'cfdi:Retenciones', 'cfdi:Retencion');
                    $cuentaTerceros = $concepto->searchNode('cfdi:ACuentaTerceros');
                    $informacionAduaneras = $concepto->searchNode('cfdi:InformacionAduanera');
                    $cuentaPredials = $concepto->searchNode('cfdi:CuentaPedial');
                    ?>
                    <tr>
                        <td><?= $this->e($concepto['Cantidad']) ?> - <?= $this->e($concepto['ClaveUnidad']) ?> <?= $this->e($concepto['Unidad'] ?: '') ?></td>
                        <td><?= $this->e($concepto['ClaveProdServ']) ?></td>
                        <td><?= wordwrap($this->e($concepto['Descripcion']), 22, '<br />') ?>
                            <?php if ('' !== $this->e($concepto['NoIdentificacion'])) : ?>
                                <br>
                                <span style="word-wrap: break-word; display: inline-block; font-size: 8px;">No identificación: <?= wordwrap($this->e($concepto['NoIdentificacion']  ?: '(ninguno)'), 22, '<br />', true) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ('' !== $this->e($concepto['ObjetoImp'])) : ?>
                                <span><?= wordwrap($catalogos->catObjetoImp($concepto['ObjetoImp']), 10, '<br />') ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= $this->e('$' . $concepto['ValorUnitario']) ?></td>
                        <td><?= $this->e($concepto['Descuento'] ?: '') ?></td>
                        <td>
                            <strong style="font-size: 10px;">Traslado:</strong>
                            <?php foreach ($conceptoTraslados as $impuesto) : ?>
                                <p style="font-size: 8px;">
                                    <?= $catalogos->catImpuesto($impuesto['Impuesto']) ?><br>
                                    <?= wordwrap('Importe: $' . $this->e($impuesto['Importe']), 10, '<br />') ?>
                                </p>
                            <?php endforeach; ?>

                            <?php if ($conceptoRetenciones->count()) : ?>
                                <strong style="font-size: 10px;">Retención</strong>:
                            <?php endif; ?>
                            <?php foreach ($conceptoRetenciones as $impuesto) : ?>
                                <p style="font-size: 8px;">
                                    <?= $catalogos->catImpuesto($impuesto['Impuesto']) ?><br>
                                    <?= wordwrap('Importe: $' . $this->e($impuesto['Importe']), 10, '<br />') ?>
                                </p>
                            <?php endforeach; ?>
                        </td>
                        <td><strong><?= $this->e('$' . $concepto['Importe']) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <hr>
    <div>
        <table style="width: 100%; font-weight: bold;">
            <tr>
                <th style="width: 76%"></th>
                <th style="width: 22%"></th>
            </tr>
            <tr>
                <td>
                </td>
                <td>
                    <table>
                        <tr>
                            <td class="total-left">
                                <span>Moneda:</span>
                            </td>
                            <td class="total-right">
                                <span><?= $this->e($comprobante['Moneda']) ?></span><br>
                            </td>
                        </tr>
                        <?php if ('' !== $comprobante['TipoCambio']) : ?>
                            <tr>
                                <td class="total-left">
                                    <span>Tipo cambio:</span><br>
                                </td>
                                <td class="total-right">
                                    <span><?= $this->e($comprobante['TipoCambio']) ?></span><br>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <tr>
                            <td class="total-left">
                                <span>Subtotal:</span><br>

                            </td>
                            <td class="total-right">
                                <span>$<?= $this->e($comprobante['SubTotal']) ?></span><br>

                            </td>
                        </tr>
                        <tr>
                            <td class="total-left">
                                <span>Descuento:</span><br>

                            </td>
                            <td class="total-right">
                                <span>$<?= $this->e($comprobante['Descuento']) ?></span><br>

                            </td>
                        </tr>
                        <tr>
                            <td class="total-left">
                                <span>Impuesto</span><br>
                                <span>Trasladado:</span>
                            </td>
                            <td class="total-right">
                                <span>$<?= $this->e($totalImpuestosTrasladados) ?></span><br>

                            </td>
                        </tr>
                        <?php if ('' !== $totalImpuestosRetenidos) : ?>
                            <tr>
                                <td class="total-left">
                                    <span>Impuesto</span><br>
                                    <span>retenido:</span>
                                </td>
                                <td class="total-right">
                                    <span>$<?= $this->e($totalImpuestosRetenidos) ?></span><br>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <tr>
                            <td class="total-left">
                                <span>Total:</span><br>
                            </td>
                            <td class="total-right">
                                <span>$<?= $this->e($comprobante['Total']) ?></span><br>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>
    <br>
    <br>
    <div class="panel">
        <div class="title">Información adicional</div>
        <div class="content">
            <?php foreach ($conceptos as $concepto) : ?>
                <?php
                $conceptoCounter = $conceptoCounter + 1;
                $conceptoTraslados = $concepto->searchNodes('cfdi:Impuestos', 'cfdi:Traslados', 'cfdi:Traslado');
                $conceptoRetenciones = $concepto->searchNodes('cfdi:Impuestos', 'cfdi:Retenciones', 'cfdi:Retencion');
                $cuentaTerceros = $concepto->searchNode('cfdi:ACuentaTerceros');
                $informacionAduaneras = $concepto->searchNode('cfdi:InformacionAduanera');
                $cuentaPredials = $concepto->searchNode('cfdi:CuentaPedial');
                ?>
                <!-- Additional information section for each concept -->
                <?php if (null !== $cuentaTerceros) : ?>
                    <p>
                        <strong>A cuenta terceros</strong>
                        Rfc a cuenta terceros: <?= $this->e($cuentaTerceros['RfcACuentaTerceros']) ?>,
                        Nombre a cuenta terceros: <?= $this->e($cuentaTerceros['NombreACuentaTerceros']) ?>,
                        Regimen fiscal a cuenta terceros:
                        <?= $catalogos->catRegimenFiscal($cuentaTerceros['RegimenFiscalACuentaTerceros']) ?>,
                        Domicilio fiscal a cuenta terceros:
                        <?= $this->e($cuentaTerceros['DomicilioFiscalACuentaTerceros']) ?>
                    </p>
                <?php endif; ?>
                <?php if (null !== $informacionAduaneras) : ?>
                    <p>
                        <strong>Informacion aduanera</strong>
                        <?php foreach ($concepto->searchNodes('cfdi:InformacionAduanera') as $informacionAduanera) : ?>
                            Pedimento: <?= $this->e($informacionAduanera['NumeroPedimento']) ?>
                        <?php endforeach; ?>
                    </p>
                <?php endif; ?>
                <?php foreach ($concepto->searchNodes('cfdi:CuentaPedial') as $cuentaPredial) : ?>
                    <p>
                        <strong>Cuenta predial: </strong><?= $this->e($cuentaPredial['Numero']) ?>
                    </p>
                <?php endforeach; ?>
                <?php foreach ($concepto->searchNodes('cfdi:Parte') as $parte) : ?>
                    <p style="padding-left: 5mm">
                        <strong>Parte: </strong><?= $this->e($parte['Descripcion']) ?>,
                        <br />
                        <span>Clave SAT: <?= $this->e($parte['ClaveProdServ']) ?>,</span>
                        <span>No identificación: <?= $this->e($parte['NoIdentificacion'] ?: '(ninguno)') ?>,</span>
                        <span>Cantidad: <?= $this->e($parte['Cantidad']) ?>,</span>
                        <span>Unidad: <?= $this->e($parte['Unidad'] ?: '(ninguna)') ?>,</span>
                        <span>Valor unitario: <?= $this->e($parte['ValorUnitario'] ?: '0') ?></span>,
                        <span>Importe: <?= $this->e($parte['Importe'] ?: '0') ?></span>
                        <?php foreach ($parte->searchNodes('cfdi:InformacionAduanera') as $informacionAduanera) : ?>
                            <br />Pedimento: <?= $this->e($informacionAduanera['NumeroPedimento']) ?>
                        <?php endforeach; ?>
                    </p>
                <?php endforeach; ?>
            <?php endforeach; ?>

        </div>
    </div>
    <?php
    $pagos = $comprobante->searchNodes('cfdi:Complemento', 'pago10:Pagos', 'pago10:Pago');
    $pagoCounter = 0;
    $pagoCount = $pagos->count();
    ?>
    <?php foreach ($pagos as $pago) : ?>
        <?php
        $pagoCounter = $pagoCounter + 1;
        $doctoRelacionados = $pago->searchNodes('pago10:DoctoRelacionado');
        ?>
        <div class="panel">
            <div class="title">Pago: <?= $this->e($pagoCounter) ?> de <?= $this->e($pagoCount) ?></div>
            <div class="content">
                <p>
                    <span><strong>Fecha de pago:</strong> <?= $this->e($pago['FechaPago']) ?>,</span>
                    <span><strong>Forma de pago:</strong> <?= $this->e($pago['FormaDePagoP']) ?>,</span>
                    <span><strong>Moneda:</strong> <?= $this->e($pago['MonedaP']) ?>,</span>
                    <span><strong>Monto:</strong> <?= $this->e($pago['Monto']) ?></span>
                    <?php if ('' !== $pago['TipoCambioP']) : ?>
                        <span><strong>Tipo Cambio:</strong> <?= $this->e($pago['TipoCambioP']) ?></span>
                    <?php endif; ?>
                    <?php if ('' !== $pago['NumOperacion']) : ?>
                        <span><strong>Número operación:</strong> <?= $this->e($pago['NumOperacion']) ?></span>
                    <?php endif; ?>
                    <?php if ('' !== $pago['RfcEmisorCtaOrd']) : ?>
                        <span><strong>RFC Emisor Cta Ord:</strong> <?= $this->e($pago['RfcEmisorCtaOrd']) ?></span>
                    <?php endif; ?>
                    <?php if ('' !== $pago['NomBancoOrdExt']) : ?>
                        <span><strong>Nombre Banco Ord Extranjero:</strong> <?= $this->e($pago['NomBancoOrdExt']) ?></span>
                    <?php endif; ?>
                    <?php if ('' !== $pago['CtaOrdenante']) : ?>
                        <span><strong>Cuenta Ord:</strong> <?= $this->e($pago['CtaOrdenante']) ?></span>
                    <?php endif; ?>
                    <?php if ('' !== $pago['RfcEmisorCtaBen']) : ?>
                        <span><strong>RFC Emisor Cta Ben:</strong> <?= $this->e($pago['RfcEmisorCtaBen']) ?></span>
                    <?php endif; ?>
                    <?php if ('' !== $pago['CtaBeneficiario']) : ?>
                        <span><strong>Cuenta Ben:</strong> <?= $this->e($pago['CtaBeneficiario']) ?></span>
                    <?php endif; ?>
                    <?php if ('' !== $pago['TipoCadPago']) : ?>
                        <span><strong>Tipo cadena de pago:</strong> <?= $this->e($pago['TipoCadPago']) ?></span>
                    <?php endif; ?>
                </p>
                <?php if ('' !== $pago['CertPago']) : ?>
                    <p>
                        <strong>Certificado de pago:</strong>
                        <span><?= $this->e($pago['CertPago']) ?></span>
                    </p>
                <?php endif; ?>
                <?php if ('' !== $pago['CadPago']) : ?>
                    <p>
                        <strong>Cadena de pago:</strong>
                        <span><?= $this->e($pago['CadPago']) ?></span>
                    </p>
                <?php endif; ?>
                <?php if ('' !== $pago['SelloPago']) : ?>
                    <p>
                        <strong>Sello de pago:</strong>
                        <span><?= $this->e($pago['SelloPago']) ?></span>
                    </p>
                <?php endif; ?>
                <?php if ($doctoRelacionados->count() > 0) : ?>
                    <p style="margin: 10px 0 10px 0;">
                        <strong>Documentos relacionados</strong>
                    </p>
                    <?php foreach ($doctoRelacionados as $doctoRelacionado) : ?>
                        <p style="margin-bottom: 10px;">
                            <strong>Id Documento: </strong><span><?= $this->e($doctoRelacionado['IdDocumento']) ?></span>
                            <strong>Serie: </strong><span><?= $this->e($doctoRelacionado['Serie']) ?></span>
                            <strong>Folio: </strong><span><?= $this->e($doctoRelacionado['Folio']) ?></span>
                            <strong>Moneda DR: </strong><span><?= $this->e($doctoRelacionado['MonedaDR']) ?></span>
                            <strong>Tipo de cambio DR: </strong>
                            <span><?= $this->e($doctoRelacionado['TipoCambioDR']) ?></span>
                            <strong>Método de pago DR: </strong>
                            <span><?= $this->e($doctoRelacionado['MetodoDePagoDR']) ?></span>
                            <strong>Número parcialidad: </strong>
                            <span><?= $this->e($doctoRelacionado['NumParcialidad']) ?></span>
                            <strong>Imp pagado: </strong><span><?= $this->e($doctoRelacionado['ImpPagado']) ?></span>
                            <strong>Imp saldo insoluto: </strong>
                            <span><?= $this->e($doctoRelacionado['ImpSaldoInsoluto']) ?></span>
                            <strong>Imp saldo anterior: </strong>
                            <span><?= $this->e($doctoRelacionado['ImpSaldoAnt']) ?></span>
                        </p>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <?php
    $pagos20 = $comprobante->searchNodes('cfdi:Complemento', 'pago20:Pagos', 'pago20:Pago');
    $pago20Count = $pagos20->count();
    ?>
    <?php foreach ($pagos20 as $pago20) : ?>
        <?php
        $pagoCounter = $pagoCounter + 1;
        $doctoRelacionados = $pago20->searchNodes('pago20:DoctoRelacionado');
        ?>
        <div class="panel">
            <div class="title">Pago: <?= $this->e($pagoCounter) ?> de <?= $this->e($pago20Count) ?></div>
            <div class="content">
                <p>
                    <span><strong>Fecha de pago:</strong> <?= $this->e($pago20['FechaPago']) ?>,</span>
                    <span><strong>Forma de pago:</strong> <?= $catalogos->catFormaPago($pago20['FormaDePagoP']) ?>,</span>
                    <span><strong>Moneda:</strong> <?= $this->e($pago20['MonedaP']) ?>,</span>
                    <span><strong>Monto:</strong> <?= $this->e($pago20['Monto']) ?></span>
                    <?php if ('' !== $pago20['TipoCambioP']) : ?>
                        <span><strong>Tipo cambio:</strong> <?= $this->e($pago20['TipoCambioP']) ?></span>
                    <?php endif; ?>
                    <?php if ('' !== $pago20['NumOperacion']) : ?>
                        <span><strong>Número operación:</strong> <?= $this->e($pago20['NumOperacion']) ?></span>
                    <?php endif; ?>
                    <?php if ('' !== $pago20['RfcEmisorCtaOrd']) : ?>
                        <span><strong>RFC emisor cta ord:</strong> <?= $this->e($pago20['RfcEmisorCtaOrd']) ?></span>
                    <?php endif; ?>
                    <?php if ('' !== $pago20['NomBancoOrdExt']) : ?>
                        <span><strong>Nombre banco ord extranjero:</strong>
                            <?= $this->e($pago20['NomBancoOrdExt']) ?></span>
                    <?php endif; ?>
                    <?php if ('' !== $pago20['CtaOrdenante']) : ?>
                        <span><strong>Cuenta ord:</strong> <?= $this->e($pago20['CtaOrdenante']) ?></span>
                    <?php endif; ?>
                    <?php if ('' !== $pago20['RfcEmisorCtaBen']) : ?>
                        <span><strong>RFC emisor cta ben:</strong> <?= $this->e($pago20['RfcEmisorCtaBen']) ?></span>
                    <?php endif; ?>
                    <?php if ('' !== $pago20['CtaBeneficiario']) : ?>
                        <span><strong>Cuenta ben:</strong> <?= $this->e($pago20['CtaBeneficiario']) ?></span>
                    <?php endif; ?>
                    <?php if ('' !== $pago20['TipoCadPago']) : ?>
                        <span><strong>Tipo cadena de pago:</strong> <?= $this->e($pago20['TipoCadPago']) ?></span>
                    <?php endif; ?>
                </p>
                <?php if ('' !== $pago20['CertPago']) : ?>
                    <p>
                        <strong>Certificado de pago:</strong>
                        <span><?= $this->e($pago20['CertPago']) ?></span>
                    </p>
                <?php endif; ?>
                <?php if ('' !== $pago20['CadPago']) : ?>
                    <p>
                        <strong>Cadena de pago:</strong>
                        <span><?= $this->e($pago20['CadPago']) ?></span>
                    </p>
                <?php endif; ?>
                <?php if ('' !== $pago20['SelloPago']) : ?>
                    <p>
                        <strong>Sello de pago:</strong>
                        <span><?= $this->e($pago20['SelloPago']) ?></span>
                    </p>
                <?php endif; ?>
                <?php if ($doctoRelacionados->count() > 0) : ?>
                    <p style="margin: 10px 0 5px 0;">
                        <strong>Documentos relacionados</strong>
                    </p>
                    <?php foreach ($doctoRelacionados as $doctoRelacionado) : ?>
                        <p style="margin-bottom: 10px;">
                            <strong>Id Documento: </strong><span><?= $this->e($doctoRelacionado['IdDocumento']) ?></span>
                            <strong>Serie: </strong><span><?= $this->e($doctoRelacionado['Serie']) ?></span>
                            <strong>Folio: </strong><span><?= $this->e($doctoRelacionado['Folio']) ?></span>
                            <strong>Moneda DR: </strong><span><?= $this->e($doctoRelacionado['MonedaDR']) ?></span>
                            <?php if ('' !== $doctoRelacionado['EquivalenciaDR']) : ?>
                                <strong>Equivalencia DR: </strong>
                                <span><?= $this->e($doctoRelacionado['EquivalenciaDR']) ?></span>
                            <?php endif; ?>
                            <strong>Número parcialidad: </strong>
                            <span><?= $this->e($doctoRelacionado['NumParcialidad']) ?></span>
                            <strong>Importe pagado: </strong><span><?= $this->e($doctoRelacionado['ImpPagado']) ?></span>
                            <strong>Importe saldo insoluto: </strong>
                            <span><?= $this->e($doctoRelacionado['ImpSaldoInsoluto']) ?></span>
                            <strong>Importe saldo anterior: </strong>
                            <span><?= $this->e($doctoRelacionado['ImpSaldoAnt']) ?></span>
                            <strong>Objeto Imp DR: </strong>
                            <span><?= $catalogos->catObjetoImp($doctoRelacionado['ObjetoImpDR']) ?></span>
                        </p>
                        <?php
                        $impuestos = $doctoRelacionado->searchNode('pago20:ImpuestosDR');
                        ?>
                        <?php if (null !== $impuestos) : ?>
                            <?php
                            $retenciones = $impuestos->searchNodes('pago20:RetencionesDR', 'pago20:RetencionDR');
                            $traslados = $impuestos->searchNodes('pago20:TrasladosDR', 'pago20:TrasladoDR');
                            ?>
                            <p style="margin: 0 10px 5px 0;">
                                <strong>Impuestos Docto Relacionado</strong>
                            </p>
                            <table style="width: 94%">
                                <tr>
                                    <th style="width: 15%">Tipo</th>
                                    <th style="width: 15%">Base</th>
                                    <th style="width: 15%">Impuesto</th>
                                    <th style="width: 15%">Tipo factor</th>
                                    <th style="width: 20%">Tasa o cuota</th>
                                    <th style="width: 20%">Importe</th>
                                </tr>
                                <?php foreach ($traslados as $impuesto) : ?>
                                    <tr>
                                        <td>Traslado</td>
                                        <td><?= $this->e($impuesto['BaseDR']) ?></td>
                                        <td><?= $catalogos->catImpuesto($impuesto['ImpuestoDR']) ?></td>
                                        <td><?= $this->e($impuesto['TipoFactorDR']) ?></td>
                                        <td><?= $this->e($impuesto['TasaOCuotaDR']) ?></td>
                                        <td>$<?= $this->e($impuesto['ImporteDR']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php foreach ($retenciones as $impuesto) : ?>
                                    <tr>
                                        <td>Retención</td>
                                        <td><?= $this->e($impuesto['BaseDR']) ?></td>
                                        <td><?= $catalogos->catImpuesto($impuesto['ImpuestoDR']) ?></td>
                                        <td><?= $this->e($impuesto['TipoFactorDR']) ?></td>
                                        <td><?= $this->e($impuesto['TasaOCuotaDR']) ?></td>
                                        <td>$<?= $this->e($impuesto['ImporteDR']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        <?php endif ?>
                        <br>
                    <?php endforeach; ?>
                <?php endif; ?>
                <?php
                $impuestos = $pago20->searchNode('pago20:ImpuestosP');
                ?>
                <?php if (null !== $impuestos) : ?>
                    <?php
                    $retenciones = $impuestos->searchNodes('pago20:RetencionesP', 'pago20:RetencionP');
                    $traslados = $impuestos->searchNodes('pago20:TrasladosP', 'pago20:TrasladoP');
                    ?>
                    <p style="margin: 10px 0 5px 0;">
                        <strong>Impuestos Pago</strong>
                    </p>
                    <table style="width: 94%">
                        <tr>
                            <th style="width: 15%">Tipo</th>
                            <th style="width: 15%">Base</th>
                            <th style="width: 15%">Impuesto</th>
                            <th style="width: 15%">Tipo factor</th>
                            <th style="width: 20%">Tasa o cuota</th>
                            <th style="width: 20%">Importe</th>
                        </tr>
                        <?php foreach ($traslados as $impuesto) : ?>
                            <tr>
                                <td>Traslado</td>
                                <td><?= $this->e($impuesto['BaseP']) ?></td>
                                <td><?= $catalogos->catImpuesto($impuesto['ImpuestoP']) ?></td>
                                <td><?= $this->e($impuesto['TipoFactorP']) ?></td>
                                <td><?= $this->e($impuesto['TasaOCuotaP']) ?></td>
                                <td>$<?= $this->e($impuesto['ImporteP']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php foreach ($retenciones as $impuesto) : ?>
                            <tr>
                                <td>Retención</td>
                                <td><?= $this->e($impuesto['BaseP']) ?></td>
                                <td><?= $catalogos->catImpuesto($impuesto['ImpuestoP']) ?></td>
                                <td><?= $this->e($impuesto['TipoFactorP']) ?></td>
                                <td><?= $this->e($impuesto['TasaOCuotaP']) ?></td>
                                <td>$<?= $this->e($impuesto['ImporteP']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    <?php
    $pagoTotales = $comprobante->searchNode('cfdi:Complemento', 'pago20:Pagos', 'pago20:Totales');
    ?>
    <?php if (null !== $pagoTotales) : ?>
        <div class="panel">
            <div class="title">Totales del complemento de pago</div>
            <div class="content">
                <p>
                    <?php if ('' !== $pagoTotales['TotalRetencionesIVA']) : ?>
                        <span>
                            <strong>Total retenciones IVA:</strong>
                            <?= $this->e($pagoTotales['TotalRetencionesIVA']) ?>
                        </span>
                    <?php endif; ?>
                    <?php if ('' !== $pagoTotales['TotalRetencionesISR']) : ?>
                        <span>
                            <strong>Total retenciones ISR:</strong>
                            <?= $this->e($pagoTotales['TotalRetencionesISR']) ?>
                        </span>
                    <?php endif; ?>
                    <?php if ('' !== $pagoTotales['TotalRetencionesIEPS']) : ?>
                        <span>
                            <strong>Total retenciones IEPS:</strong>
                            <?= $this->e($pagoTotales['TotalRetencionesIEPS']) ?>
                        </span>
                    <?php endif; ?>
                    <?php if ('' !== $pagoTotales['TotalTrasladosBaseIVA16']) : ?>
                        <span>
                            <strong>Total traslados base IVA 16:</strong>
                            <?= $this->e($pagoTotales['TotalTrasladosBaseIVA16']) ?>
                        </span>
                    <?php endif; ?>
                    <?php if ('' !== $pagoTotales['TotalTrasladosImpuestoIVA16']) : ?>
                        <span>
                            <strong>Total traslados impuesto IVA 16:</strong>
                            <?= $this->e($pagoTotales['TotalTrasladosImpuestoIVA16']) ?>
                        </span>
                    <?php endif; ?>
                    <?php if ('' !== $pagoTotales['TotalTrasladosBaseIVA8']) : ?>
                        <span>
                            <strong>Total traslados base IVA 8:</strong>
                            <?= $this->e($pagoTotales['TotalTrasladosBaseIVA8']) ?>
                        </span>
                    <?php endif; ?>
                    <?php if ('' !== $pagoTotales['TotalTrasladosImpuestoIVA8']) : ?>
                        <span>
                            <strong>Total traslados impuesto IVA 8:</strong>
                            <?= $this->e($pagoTotales['TotalTrasladosImpuestoIVA8']) ?>
                        </span>
                    <?php endif; ?>
                    <?php if ('' !== $pagoTotales['TotalTrasladosBaseIVA0']) : ?>
                        <span>
                            <strong>Total traslados base IVA 0:</strong>
                            <?= $this->e($pagoTotales['TotalTrasladosBaseIVA0']) ?>
                        </span>
                    <?php endif; ?>
                    <?php if ('' !== $pagoTotales['TotalTrasladosImpuestoIVA0']) : ?>
                        <span>
                            <strong>Total traslados impuesto IVA 0:</strong>
                            <?= $this->e($pagoTotales['TotalTrasladosImpuestoIVA0']) ?>
                        </span>
                    <?php endif; ?>
                    <?php if ('' !== $pagoTotales['TotalTrasladosBaseIVAExento']) : ?>
                        <span>
                            <strong>Total traslados base IVA exento:</strong>
                            <?= $this->e($pagoTotales['TotalTrasladosBaseIVAExento']) ?>
                        </span>
                    <?php endif; ?>
                    <span>
                        <strong>Monto total pagos:</strong>
                        <?= $this->e($pagoTotales['MontoTotalPagos']) ?>
                    </span>
                </p>
            </div>
        </div>
    <?php endif; ?>

    <?php
    $impuestos = $comprobante->searchNode('cfdi:Impuestos');
    ?>
    <?php if (null !== $impuestos) : ?>
        <?php
        $traslados = $impuestos->searchNodes('cfdi:Traslados', 'cfdi:Traslado');
        $retenciones = $impuestos->searchNodes('cfdi:Retenciones', 'cfdi:Retencion');
        ?>
        <div class="panel">
            <div class="title">Impuestos</div>
            <div class="content">
                <table style="width: 94%">
                    <tr>
                        <th style="width: 15%">Tipo</th>
                        <th style="width: 15%">Base</th>
                        <th style="width: 15%">Impuesto</th>
                        <th style="width: 15%">Tipo factor</th>
                        <th style="width: 20%">Tasa o cuota</th>
                        <th style="width: 20%">Importe</th>
                    </tr>
                    <?php foreach ($traslados as $impuesto) : ?>
                        <tr>
                            <th>Traslado</th>
                            <td><?= $this->e($impuesto['Base']) ?></td>
                            <td><?= $catalogos->catImpuesto($impuesto['Impuesto']) ?></td>
                            <td><?= $this->e($impuesto['TipoFactor']) ?></td>
                            <td><?= $this->e($impuesto['TasaOCuota']) ?></td>
                            <td><?= $this->e($impuesto['Importe']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php foreach ($retenciones as $impuesto) : ?>
                        <tr>
                            <th>Retención</th>
                            <td><?= $this->e($impuesto['Base']) ?></td>
                            <td><?= $catalogos->catImpuesto($impuesto['Impuesto']) ?></td>
                            <td><?= $this->e($impuesto['TipoFactor']) ?></td>
                            <td><?= $this->e($impuesto['TasaOCuota']) ?></td>
                            <td><?= $this->e($impuesto['Importe']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    <?php endif ?>

    <div class="panel">
        <div class="title" style="text-align: center">UUID <?= $this->e($tfd['UUID']) ?></div>
        <table class="tabular">
            <tr>
                <td rowspan="20" style="padding-right: 4mm;">
                    <!--suppress CheckEmptyScriptTag, HtmlUnknownTag -->
                    <qrcode style="width: 40mm;" ec="M" value="<?= $this->e($cfdiData->qrUrl()) ?>" />
                </td>
                <th>Tipo:</th>
                <td><?= $catalogos->catTipoComprobante($comprobante['TipoDeComprobante']) ?></td>
            </tr>
            <tr>
                <th>Certificado emisor:</th>
                <td><?= $this->e($comprobante['NoCertificado']) ?></td>
            </tr>
            <tr>
                <th>Certificado SAT:</th>
                <td><?= $this->e($tfd['NoCertificadoSAT']) ?></td>
            </tr>
            <tr>
                <th>RFC proveedor:</th>
                <td><?= $this->e($tfd['RfcProvCertif']) ?></td>
            </tr>
            <tr>
                <th>Fecha de certificación:</th>
                <td><?= $this->e($tfd['FechaTimbrado']) ?></td>
            </tr>
            <?php if ('' !== $comprobante['Exportacion']) : ?>
                <tr>
                    <th>Exportación:</th>
                    <td><?= $catalogos->catExportacion($comprobante['Exportacion']) ?></td>
                </tr>
            <?php endif; ?>
        </table>
    </div>
    <div class="panel">
        <div class="title">Datos fiscales</div>
        <div class="content">
            <table class="tabular">
                <tr>
                    <th>Sello CFDI:</th>
                    <td><?= $this->e(chunk_split($tfd['SelloCFD'], 100)) ?></td>
                </tr>
                <tr>
                    <th>Sello SAT:</th>
                    <td><?= $this->e(chunk_split($tfd['SelloSAT'], 100)) ?></td>
                </tr>
                <tr>
                    <th>Cadena TFD:</th>
                    <td><?= $this->e(chunk_split($cfdiData->tfdSourceString(), 100)) ?></td>
                </tr>
                <tr>
                    <th>Verificación:</th>
                    <td>
                        <a href="<?= $this->e($cfdiData->qrUrl()) ?>">
                            <?= $this->e(str_replace('?', "\n?", $cfdiData->qrUrl())) ?>
                        </a>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</page>
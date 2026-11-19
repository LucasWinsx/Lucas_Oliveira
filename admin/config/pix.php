<?php
// >>> CONFIGURE AQUI SUA CHAVE PIX <<<
const PIX_CHAVE  = 'LUCAS OLIVEIRA';
const PIX_NOME   = 'LUCAS OLIVEIRA';
const PIX_CIDADE = 'MONTES CLAROS';

/* true enquanto a chave Pix ainda for o texto de exemplo (o QR gerado não receberia pagamento). */
function pixConfigurado() { return PIX_CHAVE !== '' && PIX_CHAVE !== 'seu-email-ou-cpf-ou-telefone'; }

function pixTxt($s, $max) {
    $s = @iconv('UTF-8', 'ASCII//TRANSLIT', $s) ?: $s;
    return substr(strtoupper(preg_replace('/[^A-Za-z0-9 ]/', '', $s)), 0, $max);
}
function tlv($id, $v) { return $id . str_pad(strlen($v), 2, '0', STR_PAD_LEFT) . $v; }
function crc16($s) {
    $c = 0xFFFF;
    for ($i = 0; $i < strlen($s); $i++) {
        $c ^= ord($s[$i]) << 8;
        for ($j = 0; $j < 8; $j++) { $c = ($c & 0x8000) ? (($c << 1) ^ 0x1021) : ($c << 1); $c &= 0xFFFF; }
    }
    return strtoupper(str_pad(dechex($c), 4, '0', STR_PAD_LEFT));
}
// Gera o "Pix copia e cola" (BR Code) com valor e identificador do pedido.
function pixPayload($valor, $txid) {
    $conta = tlv('00', 'br.gov.bcb.pix') . tlv('01', PIX_CHAVE);
    $p = tlv('00', '01') . tlv('01', '11') . tlv('26', $conta) . tlv('52', '0000') . tlv('53', '986')
       . tlv('54', number_format($valor, 2, '.', '')) . tlv('58', 'BR')
       . tlv('59', pixTxt(PIX_NOME, 25)) . tlv('60', pixTxt(PIX_CIDADE, 15))
       . tlv('62', tlv('05', preg_replace('/[^A-Za-z0-9]/', '', $txid)))
       . '6304';
    return $p . crc16($p);
}

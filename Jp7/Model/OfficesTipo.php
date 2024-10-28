<?php

class Jp7_Model_OfficesTipo extends Jp7_Model_TipoAbstract
{
    public $attributes = [
        'type_id' => 'Offices',
        'nome' => 'Unidades',
        'campos' => 'varchar_key{,}Nome{,}{,}{,}S{,}S{,}0{,}S{,}{,}{,}{,}{,}{,}{,}{,}name{;}varchar_1{,}Endereço{,}{,}{,}S{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}address{;}varchar_9{,}Complemento{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}address2{;}varchar_2{,}Bairro{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}area{;}varchar_3{,}CEP{,}{,}{,}{,}{,}cep{,}S{,}{,}{,}{,}{,}{,}{,}{,}postal_code{;}varchar_8{,}Cidade{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}city{;}select_key{,}14{,}{,}{,}{,}S{,}0{,}S{,}{,}{,}{,}{,}Estado{,}{,}{,}state{;}varchar_4{,}Telefone{,}{,}{,}{,}{,}telefone{,}{,}{,}{,}{,}{,}{,}{,}{,}tel1{;}varchar_11{,}Telefone 2{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}tel2{;}varchar_5{,}Email{,}{,}{,}{,}S{,}email{,}{,}{,}{,}{,}{,}{,}{,}{,}email{;}file_1{,}Imagem{,}{,}{,}{,}S{,}0{,}S{,}{,}{,}{,}{,}{,}{,}{,}image{;}tit_1{,}Google Maps{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}{;}varchar_6{,}Latitude{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}latitude{;}varchar_7{,}Longitude{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}longitude{;}varchar_10{,}Zoom{,}{,}{,}{,}S{,}0{,}{,}{,}{,}{,}{,}{,}{,}14{,}zoom{;}int_key{,}Ordem{,}{,}{,}{,}{,}0{,}{,}1{,}{,}{,}{,}{,}{,}{,}ordem{;}char_key{,}Mostrar{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}mostrar{;}char_1{,}Destaque{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}featured{;}',
        'children' => '',
        'files_ajuda' => '',
        'files' => '',
        'template' => 'offices/index',
        'editpage' => '',
        'class' => '',
        'class_type' => '',
        'model_type_id' => 0,
        'tabela' => '',
        'layout' => Jp7_Box_Manager::COL_3,
        'layout_registros' => Jp7_Box_Manager::COL_2_LEFT,
        'editar' => 'S',
        'disparo' => 'Jp7_Model_OfficesTipo::checkLatLng',
    ];

    public function createChildren(InterAdminTipo $type)
    {
        parent::createBoxesSettingsAndIntroduction($type);
    }

    /**
     * Disparo no InterAdmin.
     */
    public static function checkLatLng($from, $id, $type_id)
    {
        if ($from == 'edit' || $from == 'insert') {
            if ($id && $type_id) {
                $type = InterAdminTipo::getInstance($type_id);
                $registro = $type->findById($id, [
                    'fields' => '*',
                    'fields_alias' => true,
                ]);
                if ($registro) {
                    if (!$registro->latitude && !$registro->longitude) {
                        $fullAddress = jp7_implode(', ', [
                            $registro->address,
                            $registro->postal_code,
                            $registro->city,
                        ]);
                        if ($registro->state) {
                            $fullAddress .= ' - '.$registro->state->sigla;
                        }
                        $location = Jp7_GoogleMaps::getLatLngByEndereco($fullAddress);
                        if ($location) {
                            $registro->updateAttributes([
                                'latitude' => $location->lat,
                                'longitude' => $location->lng,
                            ]);
                        }
                    }
                }
            }
        }
    }
}

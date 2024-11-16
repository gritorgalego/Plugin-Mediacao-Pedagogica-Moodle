<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Block definition class for the block_pluginname plugin.
 *
 * @package   block_mediacaopedagogica
 * @copyright 2024, Igor Thiago Marques Mendonça <igor@ifsc.edu.br>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_mediacaopedagogica extends block_base {

    /**
     * Initialises the block.
     *
     * @return void
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_mediacaopedagogica');
    }

    /**
     * Gets the block contents.
     *
     * @return string The block HTML.
     */
    public function get_content() {
        global $COURSE, $DB, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->footer = '';

        // Capturar banco de dados selecionado (se existir)
        $banco_id = optional_param('banco_id', 0, PARAM_INT);

        // Buscar todos os bancos de dados do curso atual
        $bancos = $this->get_bancos_disponiveis($COURSE->id);

        if (empty($bancos)) {
            $this->content->text = '<p>Não há bancos de dados disponíveis neste curso.</p>';
            return $this->content;
        }

        // Renderizar dropdown para selecionar banco de dados
        $this->content->text = $this->render_dropdown($bancos, $banco_id);

        // Caso um banco tenha sido selecionado, buscar as atividades
        if ($banco_id) {
            $atividades = $this->get_atividades($banco_id);
            if (empty($atividades)) {
                $this->content->text .= '<p>Não há atividades cadastradas ou vencidas neste banco de dados.</p>';
            } else {
                $this->content->text .= $this->render_atividades($atividades);
            }
        }

        return $this->content;
    }

    /**
     * Busca os bancos de dados disponíveis no curso atual.
     *
     * @param int $courseid ID do curso atual.
     * @return array Lista de bancos de dados.
     */
    private function get_bancos_disponiveis($courseid) {
        global $DB;

        // Buscar os bancos de dados (tabela mdl_data) do curso
        return $DB->get_records('data', ['course' => $courseid], 'name ASC', 'id, name');
    }

    /**
     * Renderiza o dropdown para selecionar o banco de dados.
     *
     * @param array $bancos Lista de bancos de dados.
     * @param int $banco_id ID do banco selecionado.
     * @return string HTML do dropdown.
     */
    private function render_dropdown($bancos, $banco_id) {
        $html = '<form method="post">';
        $html .= '<label for="banco_id">Selecione o Banco de Dados:</label>';
        $html .= '<select name="banco_id" id="banco_id" onchange="this.form.submit()">';

        // Adicionar opção inicial
        $html .= '<option value="0">-- Selecione --</option>';

        // Listar os bancos
        foreach ($bancos as $banco) {
            $selected = ($banco->id == $banco_id) ? 'selected' : '';
            $html .= "<option value='{$banco->id}' {$selected}>{$banco->name}</option>";
        }

        $html .= '</select>';
        $html .= '</form>';

        return $html;
    }

    /**
     * Consulta as atividades do banco de dados selecionado.
     *
     * @param int $banco_id ID do banco de dados selecionado.
     * @return array Lista de atividades.
     */
    private function get_atividades($banco_id) {
        global $DB;

        // Consultar registros do banco de dados selecionado
        $sql = "
            SELECT r.id, f.name AS atividade, c.content AS data, r.approved AS feito
            FROM {data_records} r
            JOIN {data_content} c ON r.id = c.recordid
            JOIN {data_fields} f ON f.id = c.fieldid
            WHERE r.dataid = :dataid AND f.name IN ('Data', 'Atividade')
            ORDER BY c.content ASC
        ";

        $params = ['dataid' => $banco_id];
        $resultados = $DB->get_records_sql($sql, $params);

        $atividades = [];
        $hoje = strtotime(date('Y-m-d'));

        foreach ($resultados as $resultado) {
            $data_atividade = strtotime($resultado->data);

            $atividades[] = [
                'id' => $resultado->id,
                'atividade' => $resultado->atividade,
                'data' => $resultado->data,
                'status' => $data_atividade < $hoje ? 'Atrasada' : date_diff(new DateTime($resultado->data), new DateTime())->days . ' Dias',
                'feito' => $resultado->feito
            ];
        }

        return $atividades;
    }

    /**
     * Renderiza as atividades em HTML.
     *
     * @param array $atividades Lista de atividades.
     * @return string HTML formatado.
     */
    private function render_atividades($atividades) {
        $html = '<div class="atividades">';

        foreach ($atividades as $atividade) {
            $status_color = $atividade['status'] === 'Atrasada' ? 'red' : 'green';
            $html .= "
                <div class='atividade'>
                    <p><strong>{$atividade['data']}</strong></p>
                    <p>{$atividade['atividade']}</p>
                    <p style='color: {$status_color};'>{$atividade['status']}</p>
                    <label>
                        <input type='checkbox' " . ($atividade['feito'] ? 'checked' : '') . "> Feito
                    </label>
                    <button>Finalizar Tarefa</button>
                </div>
                <hr>
            ";
        }

        $html .= '</div>';

        return $html;
    }
    
    public function applicable_formats() {
        return [
            'admin' => false,
            'site-index' => false,
            'course-view' => true,
            'mod' => false,
            'my' => false,
        ];
    }
}   
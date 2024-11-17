<?php
class block_mediacaopedagogica extends block_base {
    public function init() {
        // Nome do bloco
        $this->title = get_string('pluginname', 'block_mediacaopedagogica');
    }

    public function applicable_formats() {
        // Define onde o bloco pode ser usado
        return array(
            'course-view' => true, // Disponível na página do curso
            'site' => false,       // Não disponível na página principal
        );
    }

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
     * Busca as atividades do banco de dados selecionado.
     *
     * @param int $banco_id ID do banco de dados.
     * @return array Lista de atividades.
     */
    private function get_atividades($banco_id) {
        global $DB;

        // Consulta SQL para buscar as atividades do banco de dados
        $sql = "
            SELECT 
                dc.recordid,
                MAX(CASE WHEN df.name = 'Atividade' THEN dc.content ELSE NULL END) AS atividade,
                MAX(CASE WHEN df.name = 'Data' THEN dc.content ELSE NULL END) AS data,
                MAX(CASE WHEN df.name = 'Feito' THEN dc.content ELSE NULL END) AS feito
            FROM 
                {data_content} dc
            JOIN 
                {data_fields} df ON dc.fieldid = df.id
            JOIN 
                {data} d ON df.dataid = d.id
            WHERE 
                d.id = ?
            GROUP BY 
                dc.recordid
            ORDER BY 
                data ASC
        ";

        return $DB->get_records_sql($sql, [$banco_id]);
    }

    /**
     * Renderiza a lista de atividades no layout especificado.
     *
     * @param array $atividades Lista de atividades.
     * @return string HTML das atividades.
     */
    private function render_atividades($atividades) {
        $html = '<div class="atividade-lista">';
        foreach ($atividades as $atividade) {
            $html .= '
                <div class="atividade-item">
                    <h3>' . htmlspecialchars($atividade->atividade) . '</h3>
                    <p><strong>Data:</strong> ' . htmlspecialchars($atividade->data) . '</p>
                    <p><strong>Concluído:</strong> ' . htmlspecialchars($atividade->feito) . '</p>
                </div>
            ';
        }
        $html .= '</div>';
        return $html;
    }

    public function instance_allow_multiple() {
        // Permite múltiplas instâncias do bloco
        return true;
    }
}

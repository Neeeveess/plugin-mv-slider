<?php 
if (!class_exists('MV_Slider_Post_Type')){
  class MV_Slider_Post_Type{

    //CONSTRUTOR, CHAMANDO TODAS AS FUNCOES COM SUAS DEVIDAS AÇÕES
    function __construct(){
      add_action('init', array($this, 'create_post_type'));
      add_action('add_meta_boxes',array($this,'add_meta_boxes'));
      add_action('save_post', array($this, 'save_post'), 10, 2);
      add_action('save_post', array($this, 'save_post_quick_edit'), 10);
      add_filter('manage_mv-slider_posts_columns', array($this, 'mv_slider_cpt_columns'));
      add_action('manage_mv-slider_posts_custom_column', array($this, 'mv_slider_custom_columns'), 10,2);
      add_filter('manage_edit-mv-slider_sortable_columns', array($this, 'mv_slider_sortable_columns'));
      add_action('quick_edit_custom_box', array($this,'on_quick_edit_custom_box'), 10, 2);
      add_action('admin_footer', array($this, 'script_populate'));
    }

    //CRIANDO TIPO DE POST
    public function create_post_type(){
      register_post_type('mv-slider', array(
        'label' => esc_html__('Slider', 'mv-slider'), 
        'description' => esc_html__('Sliders','mv-slider' ),
        'labels' => array(
          'name' => esc_html__('Sliders','mv-slider' ),
          'singular_name' => esc_html__('Slider', 'mv-slider'), 
        ),
        'public' => true,
        'supports' => array('title', 'editor', 'thumbnail'),
        'hierarchical' => false, //Para definir hierarquia (pai e filho)
        'show_ui' => true, //Exibir UI geral do CPT
        'show_in_menu' => false, //Exibir no menu lateral do ADM
        'menu_position' => 5,
        'show_in_admin_bar' => true, //Mostrar na barra de ADM em cima no 'NEW'
        'show_in_nav_menus' => true, //Servir como um item de navegação
        'can_export' => true, //Poder exportar
        'has_archive' => false, //É um arquivo? ter um archive geral para exibição de todos
        'exclude_from_search' => false, //Quando pesquisar ser exibido na pesquisa
        'publicly_queryable' => true,//Permite fazer consultas/querys pelo Post type
        'show_in_rest' => true, //API que transforma todos os dados em formato JSON para ser utilizado, editor de blocos utiliza esse formato!
        'menu_icon' => 'dashicons-images-alt2',
        // 'register_meta_box_cb' => array($this,'add_meta_boxes'),
      ));
    }
    
    //NOME DAS COLUNAS
    public function mv_slider_cpt_columns($columns){
      $columns['mv_slider_link_text'] = esc_html__('Link Text', 'mv-slider');
      $columns['mv_slider_link_url'] = esc_html__('Link URL', 'mv-slider');
      return $columns;
    }

    //CONTEUDO DAS COLUNAS
    public function mv_slider_custom_columns($column, $post_id){
      switch($column){
        case 'mv_slider_link_text':
          echo esc_html(get_post_meta($post_id, 'mv_slider_link_text',true));
        break;
        case 'mv_slider_link_url':
          echo esc_url(get_post_meta($post_id, 'mv_slider_link_url',true));
        break;
      }
    }

    //SCRIPT QUE PEGA OS VALORES DO BANCO DE DADOS DO METABOX
    function script_populate(){
      ?>
      <script>
        jQuery( function( $ ){

        const wp_inline_edit_function = inlineEditPost.edit;

        // we overwrite the it with our own
        inlineEditPost.edit = function( post_id ) {

          // let's merge arguments of the original function
          wp_inline_edit_function.apply( this, arguments );

          // get the post ID from the argument
          if ( typeof( post_id ) == 'object' ) { // if it is object, get the ID number
            post_id = parseInt( this.getId( post_id ) );
          }

          // add rows to variables
          const edit_row = $( '#edit-' + post_id )
          const post_row = $( '#post-' + post_id )

          const productPrice = $( '.column-mv_slider_link_url', post_row ).text() 

          // populate the inputs with column data
          $( ':input[name="mv_slider_link_url"]', edit_row ).val( productPrice );          
          
        }
        });
      </script>
      <?php
    }

    // ADICIONANDO NA EDIÇAO RAPIDA
    function on_quick_edit_custom_box($column_name, $post_type)
    {
        if ('mv_slider_link_url' == $column_name) { 
          ?>        
          <fieldset class="inline-edit-col-left">
            <div class="inline-edit-col">
              <label>
                <span class="title">Link Url</span>
                <input 
                    type="url" 
                    name="mv_slider_link_url" 
                    id="mv_slider_link_url" 
                    class="regular-text link-url"                                
                >
              </label>
            </div>
          </fieldset>
        <?php }
    }

    //FILTRO NA COLUNA LINK_TEXT
    public function mv_slider_sortable_columns($columns){
      $columns['mv_slider_link_text'] = 'mv_slider_link_text';
      return $columns;
    }

    //ADICIONANDO METABOX
    public function add_meta_boxes(){
      add_meta_box(
        'mv_slider_meta_mox',
        esc_html__('Link Options', 'mv-slider'),
        array($this, 'add_inner_meta_boxes'),
        'mv-slider',
        'normal',
        'high',
        // array('foo' => 'bar')
      );
    }

    //PEGANDO A VIEW
    public function add_inner_meta_boxes($post){
      require_once(MV_SLIDER_PATH.'views/mv-slider_metabox.php');
    }

    //SALVANDO POST QUICK EDIT
    public function save_post_quick_edit($post_id ){
          // check inlint edit nonce
      if ( ! wp_verify_nonce( $_POST[ '_inline_edit' ], 'inlineeditnonce' ) ) {
        return;
      }

      $url = ! empty( $_POST[ 'mv_slider_link_url' ] ) ? esc_html( $_POST[ 'mv_slider_link_url' ] ) : '';
      update_post_meta( $post_id, 'mv_slider_link_url', $url );

    }

    //SALVANDO POST, COM SEGURANÇA E VERIFICAÇÕES
    public function save_post($post_id){
      if (isset($_POST['mv_slider_nonce'])){
        if (! wp_verify_nonce($_POST['mv_slider_nonce'], 'mv_slider_nonce')){
          return;
        }
      }

      if (defined('DOING_AUTOSABE') && DOING_AUTOSAVE){
        return;
      }

      if(isset($_POST['post_type']) && $_POST['post_type'] === 'mv-slider'){
        if(!current_user_can('edit_page', $post_id)){
          return;
        }elseif(!current_user_can('edit_post', $post_id)){
          return;
        }
      }

      if(isset($_POST['action']) && $_POST['action'] == 'editpost'){
        $old_link_text = get_post_meta($post_id, 'mv_slider_link_text', true);
        $new_link_text = $_POST['mv_slider_link_text'];
        $old_link_url = get_post_meta($post_id, 'mv_slider_link_url', true);
        $new_link_url = $_POST['mv_slider_link_url'];

        if(empty($new_link_text)){
        update_post_meta($post_id, 'mv_slider_link_text' , esc_html__('Add some text', 'mv-slider'));          
        }else{
          update_post_meta($post_id, 'mv_slider_link_text' , sanitize_text_field($new_link_text),$old_link_text);
        }

        if(empty($new_link_url)){
          update_post_meta($post_id, 'mv_slider_link_url' , '#');
        }else{
          update_post_meta($post_id, 'mv_slider_link_url' , sanitize_text_field($new_link_url),$old_link_url);
        }
      }
    }
  }
}
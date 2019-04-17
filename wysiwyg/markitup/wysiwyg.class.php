<?php
class cmsWysiwygMarkitup {

    private static $redactor_loaded = false;

    private $options = [
        'skin' => 'simple',
        'set' => []
    ];

    public $default_set = [
        'resizeHandle' => false,
        'onShiftEnter' => [
            'keepDefault' => false,
            'replaceWith' => "<br />\n",
        ],
        'onCtrlEnter'  => ['keepDefault' => true],
        'onTab'        => [
            'keepDefault' => false,
            'replaceWith' => '    ',
        ],
        'markupSet' => [
            [
                'name'      => LANG_MARKITUP_B,
                'key'       => 'B',
                'openWith'  => '<b>',
                'closeWith' => '</b>',
                'className' => 'btnBold',
            ],
            [
                'name'      => LANG_MARKITUP_I,
                'key'       => 'I',
                'openWith'  => '<i>',
                'closeWith' => '</i>',
                'className' => 'btnItalic',
            ],
            [
                'name'      => LANG_MARKITUP_U,
                'key'       => 'U',
                'openWith'  => '<u>',
                'closeWith' => '</u>',
                'className' => 'btnUnderline',
            ],
            [
                'name'      => LANG_MARKITUP_S,
                'key'       => 'S',
                'openWith'  => '<s>',
                'closeWith' => '</s>',
                'className' => 'btnStroke',
            ],
            [
                'name'           => LANG_MARKITUP_UL,
                'openWith'       => '    <li>',
                'closeWith'      => '</li>',
                'multiline'      => true,
                'openBlockWith'  => "<ul>\n",
                'closeBlockWith' => "\n</ul>",
                'className'      => 'btnOl',
            ],
            [
                'name'           => LANG_MARKITUP_OL,
                'openWith'       => '    <li>',
                'closeWith'      => '</li>',
                'multiline'      => true,
                'openBlockWith'  => "<ol>\n",
                'closeBlockWith' => "\n</ol>",
                'className'      => 'btnUl',
            ],
            [
                'name'      => LANG_MARKITUP_BC,
                'openWith'  => '<blockquote>[!['.LANG_MARKITUP_BC_HINT.']!]',
                'closeWith' => '</blockquote>',
                'className' => 'btnQuote',
            ],
            [
                'name'        => LANG_MARKITUP_L,
                'key'         => 'L',
                'openWith'    => '<a target="_blank" href="[!['.LANG_MARKITUP_L1.':!:http://]!]">',
                'closeWith'   => '</a>',
                'placeHolder' => LANG_MARKITUP_L2,
                'className'   => 'btnLink',
            ],
            [
                'name'        => LANG_MARKITUP_IMGL,
                'replaceWith' => '<img src="[!['.LANG_MARKITUP_IMGL1.':!:http://]!]" alt="[!['.LANG_DESCRIPTION.']!]" />',
                'className'   => 'btnImg',
            ],
            [
                'name'      => LANG_MARKITUP_IMG,
                'className' => 'btnImgUpload',
                'beforeInsert' => true
            ],
            [
                'name'      => LANG_MARKITUP_YT,
                'openWith'  => '<youtube>[!['.LANG_MARKITUP_YT1.']!]',
                'closeWith' => '</youtube>',
                'className' => 'btnVideoYoutube',
            ],
            [
                'name'      => LANG_MARKITUP_FB,
                'openWith'  => '<facebook>[!['.LANG_MARKITUP_FB1.']!]',
                'closeWith' => '</facebook>',
                'className' => 'btnVideoFacebook',
            ],
            [
                'name'        => LANG_MARKITUP_CODE,
                'openWith'    => '<code type="[!['.LANG_MARKITUP_CODE1.':!:php]!]">',
                'placeHolder' => "\n\n",
                'closeWith'   => '</code>',
                'className'   => 'btnCode',
            ],
            [
                'name'        => LANG_MARKITUP_SP,
                'openWith'    => '<spoiler title="[!['.LANG_MARKITUP_SP1.':!:'.LANG_MARKITUP_SP.']!]">',
                'placeHolder' => "\n\n",
                'closeWith'   => '</spoiler>',
                'className'   => 'btnSpoiler',
            ],
            [
                'name'      => LANG_MARKITUP_SM,
                'className' => 'btnSmiles',
                'key'       => 'Z',
                'beforeInsert' => true
            ]
        ]
    ];

    public function __construct($config = []) {

        $this->options['set'] = $this->default_set;
        $this->options['set']['data'] = [
            'smiles_url'   => href_to('typograph', 'get_smiles'),
            'upload_title' => LANG_UPLOAD,
            'upload_url'   => href_to('images', 'upload_with_preset', ['inline_upload_file', 'wysiwyg_markitup'])
        ];

        if(!empty($config['set'])){
            $this->options['set'] = array_replace_recursive($this->options['set'], $config['set']);
        }

        if(!empty($config['buttons'])){
            foreach ($this->options['set']['markupSet'] as $btn_id => $btn) {
                if(!in_array($btn_id, $config['buttons'])){
                    unset($this->options['set']['markupSet'][$btn_id]);
                }
            }
        }

    }

    public function displayEditor($field_id, $content = '', $config = []) {

        $this->loadRedactor();

        $dom_id = str_replace(array('[',']'), array('_', ''), $field_id);

        if($dom_id){
            echo html_textarea($field_id, $content, [
                'id' => $dom_id,
                'class' => 'markitup_redactor'
            ]);
        }

        ob_start(); ?>

        <script type="text/javascript">
            <?php if($dom_id){ ?>
                $(function(){
                    init_markitup('<?php echo $dom_id; ?>');
                });
            <?php } ?>
            function init_markitup (dom_id){
                var mconfig = <?php echo json_encode($this->options['set']); ?>;
                if(mconfig.markupSet[9] && mconfig.markupSet[9].beforeInsert === true){
                    mconfig.markupSet[9].beforeInsert = function(markItUp) { InlineUpload.display(markItUp); };
                }
                if(mconfig.markupSet[14] && mconfig.markupSet[14].beforeInsert === true){
                    mconfig.markupSet[14].beforeInsert = function(markItUp) { insertSmiles.displayPanel(markItUp); };
                }
                $('#'+dom_id).markItUp(mconfig);
            }
        </script>

        <?php cmsTemplate::getInstance()->addBottom(ob_get_clean());

    }

    private function loadRedactor() {

        if(self::$redactor_loaded){ return false; }

        $template = cmsTemplate::getInstance();

        $template->addJSFromContext('wysiwyg/markitup/image_upload.js');
        $template->addJSFromContext('wysiwyg/markitup/insert_smiles.js');
        $template->addJSFromContext('wysiwyg/markitup/jquery.markitup.js');
        $template->addCSSFromContext('wysiwyg/markitup/skins/'.$this->options['skin'].'/style.css');

        return true;

    }

}

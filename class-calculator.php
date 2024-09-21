<?php 
class InvestmentCalculator{

    function __construct(){
        add_action('wp_enqueue_scripts', array($this, 'enqueue'));
        add_shortcode( 'investment_calculator', array($this, 'calculator_shortcode'));
        add_action('wp_ajax_ajax_calculator_result', array($this, 'ajax_calculator_result'));
        add_action('wp_ajax_nopriv_ajax_calculator_result', array($this, 'ajax_calculator_result'));

    }

    public function enqueue(){
        wp_enqueue_style('calculator', get_template_directory_uri() . '/calculator/calculator.css');
        wp_enqueue_script('calculator', get_template_directory_uri() . '/calculator/calculator.js', array('jquery'), '1.0.0', true);
    }

    private function __fields(){
        return array(
            array(
                'name' => 'name',
                'type' => 'text',
                'label' => __('Name', 'murdeni'),
                'required' => true,
                'value' => 'Feri Murdeni',
            ),

            array(
                'name' => 'age',
                'type' => 'number',
                'label' => __('Age (Years)', 'murdeni'),
                'required' => true,
                'value' => 30,
            ),

            array(
                'name' => 'initial_value',
                'type' => 'text',
                'input_class' => array('number'),
                'label' => __('Initial Investment Value', 'murdeni'),
                'required' => true,
                'value' => 10000000,
            ),

            array(
                'name' => 'monthly_value',
                'type' => 'text',
                'input_class' => array('number'),
                'label' => __('Monthly Investment Value', 'murdeni'),
                'required' => true,
                'value' => 1000000
            ),

            array(
                'name' => 'years',
                'type' => 'text',
                'input_class' => array('number'),
                'label' => __('Investment Period (Years)', 'murdeni'),
                'required' => true,
                'value' => 30
            ),

            array(
                'name' => 'reksa_type',
                'type' => 'select_with_helper',
                'class' => 'with-helper',
                'label' => __('Type of Mutual Fund and Assumption', 'murdeni'),
                'options' => array(
                    14 => 'Reksa Dana Saham',
                    11 => 'Reksa Dana Campuran',
                    8 => 'Reksa Dana Pendapatan Tetap',
                    6 => 'Reksa Dana Pasar Uang'
                ),
            ),

            array(
                'name' => 'expected_final_value',
                'type' => 'text',
                'input_class' => array('number'),
                'label' => __('Expected Final Investment Value', 'murdeni'),
                'required' => true,
                'value' => 83258532 
            ),

        );
    }


    private function calculator_fields(){
        return array(
            'final' => array(
                'label'   => __('Jumlah Investasi Akhir', 'murdeni'),
                'fields' => array('name', 'age', 'initial_value', 'monthly_value', 'reksa_type', 'years'),
                'description' => __('Kamu dapat mengetahui nilai investasi akhir kamu dengan mengisi nilai investasi awal, nilai investasi bulanan dan jenis Reksa Dana yang diinginkan', 'murdeni'),
            ),

            'initial' => array(
                'label'   => __('Jumlah Investasi Awal', 'murdeni'),
                'fields' => array('name', 'age', 'expected_final_value', 'years', 'reksa_type'),
                'description' => __('Kamu dapat mengetahui jumlah investasi awal yang diperlukan dengan mengisi nilai investasi akhir, periode investasi dan jenis Reksa Dana yang diinginkan', 'murdeni'),
            ),
            'monthly' => array(
                'label'   => __('Jumlah Investasi Bulanan', 'murdeni'),
                'fields' => array('name', 'age', 'expected_final_value', 'years', 'reksa_type'),
                'description' => __('Kamu dapat mengetahui jumlah investasi bulanan yang diperlukan dengan mengisi nilai investasi akhir yang diinginkan, periode investasi dan jenis Reksa Dana yang diinginkan', 'murdeni'),
            ),
            'period' => array(
                'label'   => __('Periode Investasi (Awal)', 'murdeni'),
                'fields' => array('name', 'age', 'expected_final_value', 'initial_value', 'reksa_type'),
                'description' => __('Kamu dapat mengetahui periode investasi yang diperlukan dengan mengisi nilai investasi akhir, nilai investasi awal dan jenis Reksa Dana yang diinginkan.', 'murdeni'),
            ),
        );
    }

    private function product_category_map(){
        return array(
            14 => 65, // percent : category id
            11 => 68,
            8 => 66,
            6 => 67
        );
    }

    private function fieldGenerator($field_name){
        $field = array_shift(array_filter($this->__fields(), function($f) use ($field_name) {
            return $f['name'] == $field_name;
        }));
        
        return sprintf(
            '<div class="form-group %s"><label for="">%s</label>%s</div>',
            isset($field['class']) ? $field['class'] : '',
            $field['label'],
            $field['type'] == 'select_with_helper' ?
                sprintf(
                    '<div class="field-item with-helper"><select name="%s"  class="input-field">%s</select><span class="input-helper">%s </span></div>',                    
                    $field['name'],
                    implode('', array_map(function($key, $val) {
                        return sprintf('<option value="%s">%s</option>', $key, $val);
                    }, array_keys($field['options']), $field['options'])),
                    
                    array_key_first($field['options']).'%'
                    
                ) :
                
                sprintf(
                    '<input type="%s" class="field-item %s" name="%s"  placeholder="%s" value="%s" %s/>',
                    $field['type'],
                    (isset($field['input_class']) && is_array($field['input_class'])) ? implode(' ', $field['input_class']) : '',                   
                    $field['name'],
                    $field['label'],
                    (isset($field['value'])) ? $field['value'] : '',
                    (isset($field['required']) && $field['required']) ? 'required' : ''
                )
        );
    }

    

    public function calculator_shortcode($atts){
        ob_start();
        ?>
        <div id="investment-calculator">
            <?php
            $types = $this->calculator_fields();
            ?>
            <div class="calculator-nav tab-nav">
                <?php foreach($types as $type => $val): ?>
                <a href="#" class="nav-item <?php echo ($type == 'final') ? 'active' : ''; ?>" data-tab="<?php echo $type; ?>"><span><?php echo $val['label']; ?></span></a>
                <?php endforeach; ?>
            </div>

            <div class="calculator-wrapper">

                <div class="calculator-column left-column">
                    
                    <div class="tab-contents">
                        <?php foreach($types as $type => $val): ?>
                            <div class="calculator-form-container nav-content <?php echo ($type == 'final') ? 'active' : ''; ?>" data-tab="<?php echo $type; ?>">
                                <form id="calculator_<?php echo $type; ?>"class="calculator-form">
                                    <!-- Fields here -->
                                    
                                    <h2 class="heading-form"><?php _e('Kalkulator Investasi', 'murdeni'); ?></h2>
                                    <p class="subtitle-form"><?php _e('Tentukan investasi reksa dana yang sesuai dengan kebutuhanmu!', 'murdeni'); ?></p>

                                    <div class="form-group-wrapper">
                                        <?php foreach ((array) ($val['fields'] ?? []) as $field) :
                                            echo $this->fieldGenerator($field);
                                        endforeach; ?>
                                    </div>
                                        

                                    <p class="description-form"><?php echo isset($val['description']) ? $val['description'] : ''; ?></p>
                                    <input type="hidden" name="lang" value="<?php echo get_locale(); ?>" />
                                    <input type="hidden" name="form_type" value="<?php echo $type; ?>">
                                    <button class="button button-calculate button-orange" type="submit"><?php _e('Hitung', 'murdeni'); ?></button>
                                    
                                    
                                    <!-- Fields here -->
                                    <div class="calculator-disclaimer">
                                        <span class="icon icon-warning"></span>
                                        <p><?php _e('Disclaimer : Harap diperhatikan bahwa kalkulator ini disediakan hanya untuk tujuan simulasi dan tidak mencerminkan kondisi pasar saat ini.', 'murdeni'); ?></p>
                                    </div>
                                </form>
                            </div>
                        <?php endforeach; ?>
            
                    </div>
                </div>
            
                
            
                <div class="calculator-column right-column">
                   
                    <div class="calculator-result">

                        <?php foreach ($types as $type => $val) {
                            echo $this->calculators_result_html($type);
                        } ?>
                    </div>
                     <div class="default-display">
                        <?php 
                        $image_id = 5212;
                        echo wp_get_attachment_image( $image_id, 'full' ); 
                        ?>
                    </div>

            
                </div>
            </div>
        
        </div>
        <?php

        return ob_get_clean();

    }

    private function calculators_result_html($type){
        ob_start();
        ?>
        <div class="result nav-content-right <?php echo ($type == 'final') ? 'active' : ''; ?>" data-tab="<?php echo $type; ?>" >
            <p class="username"></p>                        
            <p class="information"></p>
            <p class="calculator-price"></p>
            <p><?php _e('Produk-produk ini cocok untuk kamu:', 'murdeni'); ?></p>

            <div class="result-products"></div>


            <div class="disclaimer-right">
                <h6><span class="icon icon-warning"></span><?php _e('Disclaimer', 'murdeni'); ?></h6>
                <p><?php _e('Angka diatas merupakan ilustrasi. Data yang disajikan tidak mewakili data historis dan bukan merupakan indikasi atas kinerja di masa datang. Tidak ada jaminan atas hasil investasi, asumsi return bergantung pada kondisi pasar yang dapat berubah sewaktu-waktu. Investasi melalui Reksa Dana mengandung risiko. Calon Investor wajib membaca dan memahami prospektus sebelum memutuskan untuk berinvestasi melalui Reksa Dana. Kinerja masa lalu tidak mencerminkan kinerja masa datang.', 'murdeni'); ?></p>
            </div>

            <a href="#" class="button button-orange cta-category"><?php _e('Lihat Produk Lainnya', 'murdeni'); ?></a>

        </div>

            
       
        <?php 
        return ob_get_clean();
    }

    private function result_descriptions($data) {
        $type = isset($data['form_type']) ? $data['form_type'] : 'final';
        $product_category_map = $this->product_category_map();
        $term_id = $product_category_map[$data['reksa_type']] ?? 0;
        $product_name = get_term($term_id)->name;
        $finalage = intval($data['age']) + intval($data['years']);
        

        switch ($type) {
            case 'final':                
                
                if ($data['lang'] == 'id_id') {
                    
                    $description = sprintf('Jika kamu rutin melakukan investasi di <span> %s</span>, di usia <strong> %s Tahun </strong> nilai investasi kamu akan menjadi sebesar', $product_name, $finalage);
                } else {
                    $description = sprintf('If you regularly invest in <span> %s</span>, at the age of <strong> %s Years </strong> the value of your investment will be', $product_name, $finalage);
                }

                break;
            
            case 'initial':

                if ($data['lang'] == 'id_id') {
                    $description = sprintf('Jika kamu ingin mendapatkan nilai investasi akhir sebesar <strong> Rp %s </strong> pada <span> %s </span> di usia  <strong> %s Tahun</strong>, maka kamu harus menyiapkan investasi awal sebesar', (isset($data['expected_final_value'])) ? number_format(intval($data['expected_final_value']), 0, ',', '.') : '', $product_name, $finalage);
                    
                } else {
                    $description = sprintf('If you want to get a final investment value of <strong> Rp %s </strong> in <span> %s </span> at the age of <strong> %s Years</strong>, then you must prepare an initial investment of', (isset($data['expected_final_value'])) ? number_format(intval($data['expected_final_value']), 0, ',', '.') : '', $product_name, $finalage);

                }
                break;

            case 'monthly':
                if ( $data['lang'] == 'id_id' ) {
                    $description = sprintf('Jika kamu ingin mendapatkan nilai investasi akhir sebesar <strong> Rp %s </strong> pada <span> %s </span> di usia  <strong> %s Tahun </strong>, maka sejak sekarang kamu harus menyiapkan investasi bulanan sebesar', (isset($data['expected_final_value'])) ? number_format(intval($data['expected_final_value']), 0, ',', '.') : '', $product_name, $finalage);
                    
                } else {
                    $description = sprintf('If you want to get a final investment value of <strong> Rp %s </strong> in <span> %s </span> at the age of <strong> %s Years</strong>, then from now on you must prepare a monthly investment of', (isset($data['expected_final_value'])) ? number_format(intval($data['expected_final_value']), 0, ',', '.') : '', $product_name, $finalage);
                }
                break;
                
            case 'period':
                if ($data['lang'] == 'id_id') {
                    $description = sprintf('Jika kamu ingin mendapatkan nilai investasi akhir sebesar <strong> Rp %s </strong> pada <span> %s </span> dengan modal awal <strong> Rp %s </strong>, maka target kamu akan terpenuhi dalam kurun waktu', (isset($data['expected_final_value'])) ? number_format(intval($data['expected_final_value']), 0, ',', '.') : '', $product_name, $data['initial_value']);
                    
                } else{
                    $description = sprintf('If you want to get a final investment value of <strong> Rp %s </strong> in <span> %s </span> with an initial capital of <strong> Rp %s</strong>, then your target will be met within a period of time.', (isset($data['expected_final_value'])) ? number_format(intval($data['expected_final_value']), 0, ',', '.') : '', $product_name, $data['initial_value']);
                }
                break;

            default:
                $description = '';
                break;
        }
        
        return $description;
    } 

    private function calculate_result($data){
        $type = $data['form_type'];
       

        if (!in_array($type, array('final', 'initial', 'monthly', 'period')))
            return false;

        $returnavg = $data['reksa_type'] / 100;
        
        // return $type;
        switch ($type) {
            case 'final':
                if (empty($data['years']) || empty($data['initial_value']) || empty($data['monthly_value']))
                    return false;

                $years = $data['years'];
                $initial = $data['initial_value'];
                $monthly = $data['monthly_value'];

                // expect final value 
                $result = $initial * pow(1 + $returnavg/12, $years*12) + $monthly * (pow(1 + $returnavg/12, $years*12) - 1) / ($returnavg/12);
                break;
            
            case 'initial':
                if (empty($data['years']) || empty($data['expected_final_value']))
                    return false;

                $years = $data['years'];
                $expect_final = $data['expected_final_value'];

               
                $result = $this->PV($returnavg/12, $years*12, 0, $expect_final, 0);
                break;

            case 'monthly':
                if (empty($data['years']) || empty($data['expected_final_value']))
                    return false;

                $years = $data['years'];
                $expect_final = $data['expected_final_value'];
                
                // get monthly value
                $result = $this->PMT($returnavg/12, $years*12, 0, $expect_final, 0);
                break;

            case 'period':
                if (empty($data['initial_value']) || empty($data['expected_final_value']))
                    return false;

                $initial = $data['initial_value'];
                $expect_final = $data['expected_final_value'];

                // get years
                $result = $this->NPER($returnavg/12, 0, $initial, $expect_final, 0);
                $result = number_format_i18n( $result, 2);
                $result = round($result / 12, 2);
                break;
            
        }

        return $result;
    }

    private function products_result_html($products, $term_id, $category_name){
        ob_start();
        ?>
        <div class="result-products">
            <?php foreach ($products as $post) { ?>
                
                <div class="card-result">
                    <div class="product-name">
                        <h3><a href="<?php echo get_permalink($post->ID); ?>" rel="noopener noreferrer"><?php echo $post->post_title; ?></a></h3>
                        <p><a href="<?php echo '/product/?q-cat=' . $term_id; ?>" rel="noopener noreferrer"><?php echo $category_name; ?></a></p>
                    </div>
                    <div class="product-price">
                        <div class="product-type type-<?php echo $type == 0 ? 'convensional' : 'syariah'; ?>""><?php echo $type == 0 ? __('Conventional', 'murdeni') : __('Sharia', 'murdeni') ?></div>
                        <p><span class="currency">Rp </span><?php echo sprintf('%s / %s', '<span class="price">' . number_format_i18n(123456, 0, ',', '.') . '</span>', '<span>unit</span>'); ?></p>
                    </div>
                </div>
            <?php } ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function ajax_calculator_result(){
        $formdata = array_map(function($value) {
            return !is_array($value) ? sanitize_text_field($value) : $value;
        }, $_POST);

        $category = isset($formdata['reksa_type']) ? $formdata['reksa_type'] : 0;
        $product_category_map = $this->product_category_map();
        $term_id = $product_category_map[$category] ?? 0;
        $category_name = (!empty($term_id)) ? get_term($term_id)->name : '';
        $category_link = (!empty($term_id)) ? get_term_link($term_id) : '';
        $args = array(
            'post_type' => 'star-product',
            'posts_per_page' => 3,
            'post_status' => 'publish',
            'tax_query' => array(
                array(
                    'taxonomy' => 'category',
                    'field' => 'id',
                    'terms' => $term_id
                )
            ),
            
        );
        
        

        

        $result = array(
            'args' => $args,
            'is_retail' => $is_retail,
            'username' => sprintf('Halo <strong> %s</strong>,', $formdata['name']),
            'information' => $this->result_descriptions($formdata),
            'category' => $category_name,
            'category_link' => '/product/?q-cat=' . $term_id,
        );

        $calculated = $this->calculate_result($formdata);
        $result['calculated'] = $formdata['form_type'] != 'period' ? 'Rp ' . number_format( (float) $calculated, 0, ',', '.') : sprintf(__('%s Years', 'murdeni'), $calculated);
        

        $posts = get_posts($args);
        if (!empty($posts)) {
            $result['products'] = $this->products_result_html($posts, $term_id, $category_name);
        } else {
            $result['products'] = sprintf('Tidak ada produk di kategori <a href="%s"> %s</a>', $category_link, $category_name);
        }

        echo wp_send_json_success( $result );
    }

    private function PV($rate, $nper, $pmt, $fv = 0, $type = 0) {
        if ($rate != 0) {
            $pv = ($pmt * (1 - pow(1 + $rate, -$nper)) / $rate) + ($fv / pow(1 + $rate, $nper));
        } else {
            // If the rate is 0, the present value is just the sum of payments and the future value
            $pv = -($pmt * $nper + $fv);
        }

        // Adjust if payments are made at the beginning of the period
        if ($type == 1) {
            $pv *= (1 + $rate);
        }

        return $pv;
    }

    private function PMT($rate, $nper, $pv, $fv = 0, $type = 0) {
        if ($rate != 0) {
            $pmt = ($fv * $rate) / (pow(1 + $rate, $nper) - 1);
        } else {
            // If the rate is 0, the payment is simply the future value divided by periods
            $pmt = -($pv + $fv) / $nper;
        }

        // Adjust if payments are made at the beginning of the period
        if ($type == 1) {
            $pmt /= (1 + $rate);
        }

        return $pmt;
    }

    private function NPER($rate, $pmt, $pv, $fv = 0, $type = 0) {
        if ($pmt == 0) {
            // Simple case where there are no payments
            $nper = log($fv / $pv) / log(1 + $rate);
        } else {
            // Other cases with payments
            if ($rate != 0) {
                if ($type == 1) {
                    $pmt /= (1 + $rate);
                }
                $nper = log(($pmt + $rate * $fv) / ($pmt + $rate * $pv)) / log(1 + $rate);
            } else {
                $nper = -($pv + $fv) / $pmt;
            }
        }

        return $nper;
    }


}

new InvestmentCalculator();
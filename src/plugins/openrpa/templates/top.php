<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}
?>

<div class="container">
    <div class="row" style="margin-top: 20px;">
        <h2>PS OpenRPA Plugin</h2>
    </div>
    <div class="row" style="margin-top: 20px;">
        <h4>OpenRPA用PowerShellスクリプト</h4>
        <div class="col-12">
            <a href="https://www.prime-strategy.co.jp/openiap/ps-openrpa-plugin/" target="_blank"
               rel="noopener noreferrer">PowerShellスクリプトはこちら</a>
        </div>
    </div>
    <div class="row" style="margin-top: 20px;">
        <h4>タスク管理スクリプトのセットアップ1</h4>
        <div class="col howto-div">
            <h6>1. Windowsキー」+「Rキー」出てきた検索窓に「regedit」を入力して「OK」ボタンを押す</h6>
            <a href="<?php echo esc_url( plugins_url( 'assets/images/howto/openrpa_regedit1.png', __DIR__ ) ); ?>"
               data-lightbox="image-set">
                <img src="<?php echo esc_url( plugins_url( 'assets/images/howto/openrpa_regedit1.png', __DIR__ ) ); ?>"/>
            </a>
        </div>

        <div class="col howto-div">
            <h6>2. 新規でOpenRPAのレジストリに「APPKEY」、「HOST」、「USERNAME」を文字列値で登録</h6>
            <a href="<?php echo esc_url( plugins_url( 'assets/images/howto/openrpa_regedit2.png', __DIR__ ) ); ?>"
               data-lightbox="image-set">
                <img width="500"
                     src="<?php echo esc_url( plugins_url( 'assets/images/howto/openrpa_regedit2.png', __DIR__ ) ); ?>"/>
            </a>
        </div>

        <div class="col howto-div">
            <h6>3. 「Windowsキー」+「Rキー」出てきた検索窓に「taskschd.msc」を入力して「OK」ボタンを押す</h6>
            <a href="<?php echo esc_url( plugins_url( 'assets/images/howto/openrpa_taskschd1.png', __DIR__ ) ); ?>"
               data-lightbox="image-set">
                <img src="<?php echo esc_url( plugins_url( 'assets/images/howto/openrpa_taskschd1.png', __DIR__ ) ); ?>"/>
            </a>
        </div>

        <div class="col howto-div">
            <h6>4. 起動したタスクスケジューラの上タブ「操作(A)」を押して「タスクの作成(R)」を選択</h6>
            <a href="<?php echo esc_url( plugins_url( 'assets/images/howto/openrpa_taskschd2.png', __DIR__ ) ); ?>"
               data-lightbox="image-set">
                <img width="500"
                     src="<?php echo esc_url( plugins_url( 'assets/images/howto/openrpa_taskschd2.png', __DIR__ ) ); ?>"/>
            </a>
        </div>

        <div class="col howto-div">
            <h6>5. 上タブ「全般」で「名前(M)」を登録 ※例)openrpa 「セキュリティオプション」は「ユーザがログインしているかどうかにかかわらず実行する(W)」を選択</h6>
            <a href="<?php echo esc_url( plugins_url( 'assets/images/howto/openrpa_taskschd3.png', __DIR__ ) ); ?>"
               data-lightbox="image-set">
                <img width="500"
                     src="<?php echo esc_url( plugins_url( 'assets/images/howto/openrpa_taskschd3.png', __DIR__ ) ); ?>"/>
            </a>
        </div>

        <div class="col howto-div">
            <h6>6.
                上タブ「トリガー」で「新規(N)」を押す、「1回」を選択して開始時刻を設定、「詳細設定」欄から「繰り返し間隔」にチェックを入れて、「5分間」を選択して「OK」ボタンを押す</h6>
            <a href="<?php echo esc_url( plugins_url( 'assets/images/howto/openrpa_taskschd4.png', __DIR__ ) ); ?>"
               data-lightbox="image-set">
                <img width="500"
                     src="<?php echo esc_url( plugins_url( 'assets/images/howto/openrpa_taskschd4.png', __DIR__ ) ); ?>"/>
            </a>
        </div>

        <div class="col howto-div">
            <h6>7. 上タブ「操作」で「新規(N)」を押す、「設定」欄の「プログラム/スクリプト(P)」にはpowershellの実行ファイルパスを設定、「引数の追加(オプション)(A)」には「-Command
                "{openrpa用スクリプトパス}"」を設定、「開始(オプション)(T)」には「{openrpa用スクリプトがあるディレクトリパス}」を設定して「OK」ボタンを押す</h6>
            <a href="<?php echo esc_url( plugins_url( 'assets/images/howto/openrpa_taskschd5.png', __DIR__ ) ); ?>"
               data-lightbox="image-set">
                <img width="500"
                     src="<?php echo esc_url( plugins_url( 'assets/images/howto/openrpa_taskschd5.png', __DIR__ ) ); ?>"/>
            </a>
        </div>

        <div class="col howto-div">
            <h6>8. 「OK」ボタンを押して作成したタスクが登録されていることを確認</h6>
            <a href="<?php echo esc_url( plugins_url( 'assets/images/howto/openrpa_taskschd6.png', __DIR__ ) ); ?>"
               data-lightbox="image-set">
                <img width="500"
                     src="<?php echo esc_url( plugins_url( 'assets/images/howto/openrpa_taskschd6.png', __DIR__ ) ); ?>"/>
            </a>
        </div>
    </div>


    <div class="row" style="margin-top: 20px;">
        <h4>タスク管理スクリプトのセットアップ2</h4>
        <div class="col howto-div">
            <h6>1. 「Windowsキー」+「Rキー」出てきた検索窓に「taskschd.msc」を入力して「OK」ボタンを押す</h6>
            <a href="<?php echo esc_url( plugins_url( 'assets/images/howto/openrpa_taskschd1.png', __DIR__ ) ); ?>"
               data-lightbox="image-set">
                <img src="<?php echo esc_url( plugins_url( 'assets/images/howto/openrpa_taskschd1.png', __DIR__ ) ); ?>"/>
            </a>
        </div>

        <div class="col howto-div">
            <h6>2. 起動したタスクスケジューラの上タブ「操作(A)」を押して「基本タスクの作成(B)」を選択</h6>
            <a href="<?php echo esc_url( plugins_url( 'assets/images/howto/openrpa_taskschd7.png', __DIR__ ) ); ?>"
               data-lightbox="image-set">
                <img width="500"
                     src="<?php echo esc_url( plugins_url( 'assets/images/howto/openrpa_taskschd7.png', __DIR__ ) ); ?>"/>
            </a>
        </div>

        <div class="col howto-div">
            <h6>3. 「基本タスクの作成」では、わかりやすいように「名前(A)」や「説明(D)」を登録 ※例)openrpa log</h6>
            <a href="<?php echo esc_url( plugins_url( 'assets/images/howto/openrpa_taskschd8.png', __DIR__ ) ); ?>"
               data-lightbox="image-set">
                <img width="500"
                     src="<?php echo esc_url( plugins_url( 'assets/images/howto/openrpa_taskschd8.png', __DIR__ ) ); ?>"/>
            </a>
        </div>

        <div class="col howto-div">
            <h6>4. 「トリガー」では「コンピュータの起動時(H)」を選択する</h6>
            <a href="<?php echo esc_url( plugins_url( 'assets/images/howto/openrpa_taskschd9.png', __DIR__ ) ); ?>"
               data-lightbox="image-set">
                <img width="500"
                     src="<?php echo esc_url( plugins_url( 'assets/images/howto/openrpa_taskschd9.png', __DIR__ ) ); ?>"/>
            </a>
        </div>

        <div class="col howto-div">
            <h6>5. 「操作」では「プログラムの開始(T)」を選択する</h6>
            <a href="<?php echo esc_url( plugins_url( 'assets/images/howto/openrpa_taskschd10.png', __DIR__ ) ); ?>"
               data-lightbox="image-set">
                <img width="500"
                     src="<?php echo esc_url( plugins_url( 'assets/images/howto/openrpa_taskschd10.png', __DIR__ ) ); ?>"/>
            </a>
        </div>

        <div class="col howto-div">
            <h6>6. 「プログラムの開始」ではpowershellの実行ファイルパスを設定、「引数の追加(オプション)(A)」には「-Command
                "{ログチェック用openrpaスクリプトパス}"」を設定、「開始(オプション)(T)」には「{ログチェック用openrpaスクリプトがあるディレクトリパス}」を設定する</h6>
            <a href="<?php echo esc_url( plugins_url( 'assets/images/howto/openrpa_taskschd11.png', __DIR__ ) ); ?>"
               data-lightbox="image-set">
                <img width="500"
                     src="<?php echo esc_url( plugins_url( 'assets/images/howto/openrpa_taskschd11.png', __DIR__ ) ); ?>"/>
            </a>
        </div>

        <div class="col howto-div">
            <h6>7. 「完了」では設定した内容に間違いがないかを確認確認、問題なければ「完了(F)」を押す</h6>
            <a href="<?php echo esc_url( plugins_url( 'assets/images/howto/openrpa_taskschd12.png', __DIR__ ) ); ?>"
               data-lightbox="image-set">
                <img width="500"
                     src="<?php echo esc_url( plugins_url( 'assets/images/howto/openrpa_taskschd12.png', __DIR__ ) ); ?>"/>
            </a>
        </div>

        <div class="col howto-div">
            <h6>8. 作成したタスクが登録されていることを確認</h6>
            <a href="<?php echo esc_url( plugins_url( 'assets/images/howto/openrpa_taskschd13.png', __DIR__ ) ); ?>"
               data-lightbox="image-set">
                <img width="500"
                     src="<?php echo esc_url( plugins_url( 'assets/images/howto/openrpa_taskschd13.png', __DIR__ ) ); ?>"/>
            </a>
        </div>

    </div>
</div>

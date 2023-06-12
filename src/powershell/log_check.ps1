#レジストリに設定されている設定値を取りに行く
$BASE_URL = 'https://'

$BASE_PREFIX = '/wp-json/openrpa/v1'
$POST_TASK_ENDPOINT = '/task'

$DESKTOP_PATH = [System.Environment]::GetFolderPath('Desktop')
$AUTH_DIRECTORY = '\ps-openrpa-dir'
$AUTH_FILE = '\auth.txt'

$LOG_PATH = [System.Environment]::GetFolderPath('MyDocuments')
$LOG_FILE = $LOG_PATH + '\OpenRPA\logfile.txt'

$CHECK_SPAN = 60
$UNCOMPLETED = @()

$TYPE_HMS = 'HMS'
$TYPE_MS = 'MS'
$TYPE_START = 'start'
$TYPE_END = 'end'

function check_auth_file() {
    $dir_path = $DESKTOP_PATH + $AUTH_DIRECTORY
    $file_path = $dir_path + $AUTH_FILE
    if ( ( Test-Path $dir_path ) -ne 'True' ) {
        return $false
    }
    if ( ( Test-Path $file_path ) -ne 'True' ) {
        return $false
    }
    return $true
}

function read_auth_file() {
    $file_path = $DESKTOP_PATH + $AUTH_DIRECTORY + $AUTH_FILE
    $content = Get-Content $file_path
    $USERID_IDX = 0
    $TOKEN_IDX = 1
    $login = @{
        'userId' = $content[$USERID_IDX].Replace('`n', '')
        'token' = $content[$TOKEN_IDX].Replace('`n', '')
    }
    return $login
}

function ignore_ssl_check() {
add-type @"
    using System.Net;
    using System.Security.Cryptography.X509Certificates;
    public class TrustAllCertsPolicy : ICertificatePolicy {
        public bool CheckValidationResult(
            ServicePoint srvPoint, X509Certificate certificate,
            WebRequest request, int certificateProblem) {
            return true;
        }
    }
"@
[System.Net.ServicePointManager]::CertificatePolicy = New-Object TrustAllCertsPolicy
}

function init_parameters() {
    $reg_path = 'HKCU:HKEY_CURRENT_USER\SOFTWARE\OpenRPA'
    $data = Get-ItemProperty $reg_path
    $BASE_URL = $BASE_URL + $data.HOST
}

# PCの起動時に一度のみ実行されるスクリプト -> ログのファイル名変更 + 新規で空ログファイル作成
function init_openrpa_log() {
    $file_prefix = Get-Date -UFormat %Y%m%d_%h%M%S
    $filename = 'logfile.txt_' + $file_prefix
    Rename-Item $LOG_FILE $filename
    New-Item $LOG_FILE
}

function time_to_sec( $time, $type ) {
    $res = -1.0
    if ( $type -eq $TYPE_HMS ) {
        $H_IDX = 0
        $M_IDX = 1
        $S_IDX = 2

        $time = $time -split ':'
        $hours = $time[$H_IDX]
        $minutes = $time[$M_IDX]
        $seconds = $time[$S_IDX]

        $res = 3600*[int]$hours + 60*[int]$minutes + ([float]$seconds).ToString('0.0000')

    } else {
        $M_IDX = 0
        $S_IDX = 1
        $time = $time -split ':'
        
        $minutes = $time[$M_IDX]
        $seconds = $time[$S_IDX]

        $res = 60*[int]$minutes + ([float]$seconds).ToString('0.0000')
    }

    return $res
}

function parse_line( $line, $type ) {
    $NAME_IDX = 0
    $STIME_IDX = 0
    $DTIME_IDX = 1
    $INFO_IDX = 1
 
    $items = $line -split '\|INFO\|'

    if ( $type -eq $TYPE_START ) {
        $info = $items[$INFO_IDX] -split ' started in '
    } else {
        $info = $items[$INFO_IDX] -split ' completed in '
    }
    
    $start_time = $items[$STIME_IDX]
    $diff_time = $info[$DTIME_IDX]
    $name = $INFO[$NAME_IDX]

    $start_time = time_to_sec $start_time $TYPE_HMS
    $diff_time = time_to_sec $diff_time $TYPE_MS
    
    if ( $type -eq $TYPE_START ) {
        $time = $start_time + $diff_time
    } else {
        $time = $start_time - $diff_time
    }
    
    return ($name + '_' + [int]$time)
}


# ログ内からstartとendを取得して返す
function check_log_file( $before ) {
    $dict = @{}

    $start_str = 'started in'
    $end_str = 'completed in'

    $contents = Get-Item $LOG_FILE
    $fp = $contents.OpenText()
    $content = $fp.ReadLine()
    # startとendをそれぞれ切り出す
    while ( $null -ne $content ) {
        $length = $content.Length
        $start = $content.Substring( $length - 20, 10 )
        $end = $content.Substring( $length - 22, 12 )
        if ( $start -eq $start_str ) {
            $key = parse_line $content $TYPE_START
            $dict[$key] = @($content)
        } elseif ( $end -eq $end_str ) {
            $key = parse_line $content $TYPE_END
            if ( $dict.ContainsKey($key) ) {
                $dict[$key] += $content
            }
        }
        $content = $fp.ReadLine()
    }
    $fp.Close()

    <# StreamReader
        $fp = New-Object System.IO.StreamReader($LOG_FILE, [System.Text.Encoding]::GetEncoding("sjis"))
        while (($content = $fp.ReadLine()) -ne $null) {
            $length = $content.Length
            $start = $content.Substring( $length - 20, 10 )
            $end = $content.Substring( $length - 22, 12 )
            if ( $start -eq $start_str ) {
                $key = parse_line $content $TYPE_START
                $dict[$key] = @($content)
            } elseif ( $end -eq $end_str ) {
                $key = parse_line $content $TYPE_END
                if ( $dict.ContainsKey($key) ) {
                    $dict[$key] += $content
                }
            }
        }
        $fp.Close()
    #>

    # startとendのペアを抽出
    $pairs = [ordered]@{}
    foreach( $key in $dict.Keys ) {
        if ( $dict[$key].Count -eq 2 ) {
            foreach( $item in $dict[$key] ) {
                $pairs[$item] = $item
            }
        }
    }

    $response = [ordered]@{}
    # 前回確認したログと重複しないようにする
    foreach( $key in $pairs.Keys ){
        $item = $pairs[$key]
        if ( $before.Contains($item) ) {
            continue
        } else {
            $response[$key] = $item
        }
    }
    return $pairs, $response
}

function get_time_and_name_from_line( $line ) {
    $TIME_IDX = 0
    $ITEM_IDX = 1

    $TIME_FLOOR_IDX = 0
    $NAME_IDX = 0

    $content = $line -split '\|INFO\|'
    $time = $content[$TIME_IDX]
    $time = $time -split '\.'

    # ミリ秒以下除外の時間を取得
    $time = $time[$TIME_FLOOR_IDX]

    $items = $content[$ITEM_IDX]
    $items = $items -split ' '

    # OpenRPA側のタスク名取得
    $name = $items[$NAME_IDX]

    return $time, $name
}

# 完了しているタスクをプラグイン側にPOST
function post_tasks( $tasks ) {
    # 順序付き辞書を配列に戻す -> 引数のtasksのkeyとvalueは同じものが入っている
    $tasks_array = @()
    foreach($task in $tasks.Keys){
        $tasks_array += $task
    }

    if ( check_auth_file ) {
        $auth = read_auth_file
        $user_id = $auth.userId
        $token = $auth.token
        
        $TIME_IDX = 0
        $NAME_IDX = 1

        $uri = $BASE_URL + $BASE_PREFIX + $POST_TASK_ENDPOINT + '/' + $user_id
        $headers = @{
            'Content-Type' = 'application/json'
            'token' = $token
        }

        $post_data = @{
            'id' = $user_id
            'name' = ''
            'command' = ''
            'start' = ''
            'end' = ''
            'status' = 1
        }

        # POST未消化タスクのPOST
        for ( $idx = 0; $idx -lt $UNCOMPLETED.Count/2; $idx++ ) {
            # 開始データ -> nameは同じ
            $objes = get_time_and_name_from_line $tasks_array[$idx*2]
            $start = $objes[$TIME_IDX]
            $name = $objes[$NAME_IDX]

            # 終了データ -> nameは同じ
            $objes = get_time_and_name_from_line $tasks_array[$idx*2+1]
            $end = $objes[$TIME_IDX]
            $name = $objes[$NAME_IDX]

            # postデータ準備
            $post_data.name = $name
            $post_data.start = $start
            $post_data.end = $end

            $body = ConvertTo-Json $post_data
            $body = [Text.Encoding]::UTF8.GetBytes($body)

            ignore_ssl_check
            $resp = Invoke-RestMethod -Method Post -Uri $uri -Headers $headers -Body $body
        }
        # 未消化タスクが完了したら配列を空にする
        $UNCOMPLETED = @()
        # 今回のタスクのPOST
        for ( $idx = 0; $idx -lt $tasks_array.Count/2; $idx++ ) {
            # 開始データ -> nameは同じ
            $objes = get_time_and_name_from_line $tasks_array[$idx*2]
            $start = $objes[$TIME_IDX]
            $name = $objes[$NAME_IDX]

            # 終了データ -> nameは同じ
            $objes = get_time_and_name_from_line $tasks_array[$idx*2+1]
            $end = $objes[$TIME_IDX]
            $name = $objes[$NAME_IDX]

            # postデータ準備
            $post_data.name = $name
            $post_data.start = $start
            $post_data.end = $end

            $body = ConvertTo-Json $post_data
            $body = [Text.Encoding]::UTF8.GetBytes($body)

            ignore_ssl_check
            $resp = Invoke-RestMethod -Method Post -Uri $uri -Headers $headers -Body $body
        }
        
    } else {
        # 一時的なネットワークエラーや登録し始めの場合はまだ認証情報がない場合もあるのでPOST未消化配列に追加
        foreach( $task in $tasks_array ) {
            $UNCOMPLETED += $task
        }
    }
}

# メインWhileループ -> ここで一定間隔での実行や完了したタスクをPOSTする
function manager() {
    $completed = [ordered]@{}
    while ( 1 ) {

        $objes = check_log_file $completed
        $completed = $objes[0]
        $tasks = $objes[1]
        post_tasks $tasks

        Start-Sleep -s $CHECK_SPAN
    }
}

function main() {
    # レジストリに保存しているデータ取得
    . init_parameters
    init_openrpa_log
    manager
}

main
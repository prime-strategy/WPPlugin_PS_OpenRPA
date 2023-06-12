#レジストリに設定されている設定値を取りに行く
$BASE_URL = 'https://'
$USERNAME = ''
$APPKEY = ''
$OPENRPA_PATH = ''


$BASE_PREFIX = '/wp-json/openrpa/v1'
$LOGIN_ENDPOINT = '/login'
$GET_TASK_ENDPOINT = '/user'


$DESKTOP_PATH = [System.Environment]::GetFolderPath('Desktop')
$AUTH_DIRECTORY = '/ps-openrpa-dir'
$AUTH_FILE = '/auth.txt'



function check_auth_file() {
    $dir_path = $DESKTOP_PATH + $AUTH_DIRECTORY
    $file_path = $dir_path + $AUTH_FILE
    if ( ( Test-Path $dir_path ) -ne 'True' ) {
        New-Item $dir_path -ItemType Directory
        return $false
    }
    if ( ( Test-Path $file_path ) -ne 'True' ) {
        return $false
    }
    return $true
}

function create_auth_file( $user_id, $token ) {
    $file_path = $DESKTOP_PATH + $AUTH_DIRECTORY + $AUTH_FILE
    Set-Content $file_path -Value $user_id -Encoding utf8
    Add-Content $file_path -Value $token -Encoding utf8
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

function login( $username, $appkey ) {
    $uri = $BASE_URL + $BASE_PREFIX + $LOGIN_ENDPOINT
    $headers = @{
        'Content-Type' = 'application/json'
        'USERNAME' = $username
        'APPLICATIONKEY' = $appkey
    }
    ignore_ssl_check
    $resp = Invoke-RestMethod -Method Get -Uri $uri -Headers $headers
    return $resp
}

function get_tasks( $user_id, $token ) {
    $uri = $BASE_URL + $BASE_PREFIX + $GET_TASK_ENDPOINT + '/' + $user_id + '/task'
    $headers = @{
        'Content-Type' = 'application/json'
        'token' = $token
    }
    ignore_ssl_check
    $resp = Invoke-RestMethod -Method Get -Uri $uri -Headers $headers
    return $resp
}

function register_tasks( $tasks ) {
    foreach( $task in $tasks ) {
        Write-Host $task.schedule
        $schedule = $task.schedule -split ' '
        [System.DateTime]$schedule = $schedule[1]
        $trigger = New-ScheduledTaskTrigger -Once -At $schedule
        $task_command = $task.command
        $action = New-ScheduledTaskAction -Execute $OPENRPA_PATH -Argument "/workflowid `"$task_command`""

        $reg = Register-ScheduledTask -TaskName $task.name -Action $action -Trigger $trigger -Force

        Write-Host $schedule
        Write-Host $reg
    }
}

function init_parameters() {
    $reg_path = 'HKCU:HKEY_CURRENT_USER\SOFTWARE\OpenRPA'
    $data = Get-ItemProperty $reg_path
    $BASE_URL = $BASE_URL + $data.HOST
    $USERNAME = $data.USERNAME
    $APPKEY = $data.APPKEY
    $OPENRPA_PATH = '"' + $data.InstallFolder + 'OpenRPA.exe' + '"'
}

function main() {
    # レジストリに保存しているデータ取得
    . init_parameters

    $check = check_auth_file
    if ( -! $check ) {
        $login = login $USERNAME $APPKEY
        create_auth_file $login.userId $login.token
    } else {
        $login = read_auth_file
    }

    $tasks = get_tasks $login.userId $login.token

    <#
    # デバッグ用 -> 取得してきたタスクの確認
    foreach ( $task in $tasks ){
        Write-Host $task
    }
    #>

    # 5分以内に実行するタスクがあればスケジュール登録
    if ( $tasks.Length -ne 0 ) {
        register_tasks $tasks
    }
}

main
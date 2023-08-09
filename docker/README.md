# WordPress プラグイン開発環境

## Docker 開発環境

Docker 環境構築

```sh
cd ./docker
docker compose up -d
```

コンテナ内に入って、 `wp-install.sh` の内容を 1 行ずつ実行してください。

### Web 画面側の情報

http://localhost:8080/ にアクセスします。

- ユーザー名: wordpress (docker-compose.yml に記載)
- パスワード: wordpress (docker-compose.yml に記載)

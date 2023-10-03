# How to Release and Publish to WordPress.org Plugin Directory

main ブランチで Release すると、GitHub Actions が wordpress.org へ公開します。

## 手順

1. Release ページから Draft Release 作成画面に遷移
2. tag 作成
3. title 'Version X.Y(.Z)' 指定
4. リリース文作成
5. Publish
6. 自動で GitHub Actions がバージョン情報に従ったリリースを WordPress.org SVN にプッシュ

## 確認

- wordpress.org 公式プラグインページでリリースを確認 https://wordpress.org/plugins/ps-openrpa/
- GitHub Actions 実行履歴を確認

## 環境構築

1. public_html 配下の .env.example を複製し .env を作成

2. 作成した .env の中身を下記に変更し保存

    ~~~ sh
    APP_TIMEZONE=Asia/Tokyo
    DB_HOST=db
    DB_PORT=5432
    DB_DATABASE=crmsystem2
    DB_USERNAME=root
    DB_PASSWORD=Root2020
    MAIL_MAILER=smtp
    MAIL_HOST=mailpit
    MAIL_PORT=1025
    ~~~

3. 起動

    ~~~ sh
    cd docker
    ~~~

    ~~~ sh
    docker compose up -d --build
    ~~~

4. 起動したらコンテナに入る

    ~~~ sh
    docker compose exec app sh
    ~~~

5. コンテナ内 `/var/www #` の状態で下記を実行

    ~~~ sh
    chown -R apache:apache /var/www/storage
    ~~~

    ~~~ sh
    chmod -R 775 /var/www/storage
    ~~~

    ~~~ sh
    composer install
    ~~~

    ~~~ sh
    php artisan key:generate
    ~~~

    ~~~ sh
    php artisan migrate --seed
    ~~~

6. ブラウザで確認

    http://localhost:8081


7. フロントエンド（必要な時のみ）

    ~~~ sh
    docker compose run --rm node npm install
    docker compose run --rm node npm run build
    ~~~

8. メール確認 (Mailpit): http://localhost:8026

9. ディスク容量の解放（ビルドキャッシュ削除）

    ~~~ sh
    docker builder prune -f
    docker image prune -f
    ~~~

10. ポート設定（他のプロジェクトと同時に起動する場合）

    ホスト側のポートは `docker/.env` で変更できます。

    | 変数 | 既定値 | 用途 |
    |---|---|---|
    | `APP_PORT` | 8081 | Web (http://localhost:8081) |
    | `DB_FORWARD_PORT` | 15434 | PostgreSQL（ホストからの接続用） |
    | `MAILPIT_PORT` | 8026 | Mailpit |
    | `VITE_PORT` | 5174 | Vite 開発サーバー |

    `COMPOSE_PROJECT_NAME`（既定値 `crmsystem2`）も他のプロジェクトと別の名前にしてください（同じ名前だとコンテナとボリュームが共有されます）。
    変更後は `docker compose down && docker compose up -d` で再起動します。

11. テスト用データベース

    新しいボリュームで初回起動すると `docker/initdb` のスクリプトが `crmsystem2_testing` を作成します。
    既存のボリュームの場合は手動で作成します。

    ~~~ sh
    docker compose exec db psql -U root -d postgres -c "CREATE DATABASE crmsystem2_testing;"
    ~~~

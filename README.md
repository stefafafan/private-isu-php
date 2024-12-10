# private-isu-php

[catatsuy/private-isu](https://github.com/catatsuy/private-isu) をPHPで解いた時のログです。

## 環境

README記載の競技者用 (Ubuntu 24.04)のAMIを利用 (`x86_64`, `c7a.large`)。

ベンチマーカーインスタンスも同様 (`x86_64`, `c7a.xlarge`)。

## 構成

- `docs/` ここに作業ログなどを書きます
  - [点数ログ](./docs/点数ログ.md)
  - [作業詳細ログ](./docs/作業詳細ログ.md)

## やりたいことメモ
- [x] 他言語と同じような改善をする
- [x] JIT有効化
- [x] PHP 8.4 へバージョンアップ
- [ ] FPMやめて、RoadRunnerへ移行する
- [ ] プリロード
- [ ] 何らかのキャッシュ機構を検討
- [ ] あらゆるログ・使っていないサービスを切る

## 参考資料

- [達人が教えるWebパフォーマンスチューニング 〜ISUCONから学ぶ高速化の実践：書籍案内｜技術評論社](https://gihyo.jp/book/2022/978-4-297-12846-3)
  - 特に付録A「private-isuの攻略実践」をとても参考にしています
- [PHPerが ISUCONでやるべき事 - Speaker Deck](https://speakerdeck.com/uzulla/phperga-isucondeyarubekishi)
- [プロファイル結果の可視化三本勝負 in PHP - Speaker Deck](https://speakerdeck.com/uzulla/purohuairujie-guo-falseke-shi-hua-san-ben-sheng-fu-in-php)
  - [GitHub - uzulla/xhprof-flamegraphs](https://github.com/uzulla/xhprof-flamegraphs)
- [時間を気にせず普通にカンニングもしつつ ISUCON12 本選問題を PHP でやってみる - Speaker Deck](https://speakerdeck.com/sji/shi-jian-woqi-nisezupu-tong-nikanningumositutu-isucon12-ben-xuan-wen-ti-wo-php-deyatutemiru)
  - [GitHub - sj-i/isucon12f-phperkaigi2023-repo](https://github.com/sj-i/isucon12f-phperkaigi2023-repo)
- [Reli を使った PHP 7.x/8.x サービスの計測｜技術ブログ｜北海道札幌市・宮城県仙台市のVR・ゲーム・システム開発 インフィニットループ](https://www.infiniteloop.co.jp/tech-blog/2023/03/profiling-php8-using-reli/)
  - [GitHub - reliforp/reli-prof](https://github.com/reliforp/reli-prof)
- [private-isuをdockerでやってみた時のメモ](https://zenn.dev/eichisanden/scraps/7798c55153787b)
- [RoadRunnerの世界 〜 Yet Another Alt PHP-FPM - Speaker Deck](https://speakerdeck.com/n1215/roadrunnerfalseshi-jie-yet-another-alt-php-fpm)
- [PHP 8.4 Installation and Upgrade guide for Ubuntu and Debian • PHP.Watch](https://php.watch/articles/php-84-install-upgrade-guide-debian-ubuntu)
- [PHP: 接続、および接続の管理 - Manual](https://www.php.net/manual/ja/pdo.connections.php)
- [MySQL :: MySQL 9.1 Reference Manual :: 2.5.2 Installing MySQL on Linux Using the MySQL APT Repository](https://dev.mysql.com/doc/refman/9.1/en/linux-installation-apt-repo.html)

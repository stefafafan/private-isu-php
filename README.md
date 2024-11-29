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
- 他言語と同じような改善をする
- プロファイラ導入
  - reli-prof?
- JIT有効化
- FPMやめる

## 参考資料

- [PHPerが ISUCONでやるべき事 - Speaker Deck](https://speakerdeck.com/uzulla/phperga-isucondeyarubekishi)
- [プロファイル結果の可視化三本勝負 in PHP - Speaker Deck](https://speakerdeck.com/uzulla/purohuairujie-guo-falseke-shi-hua-san-ben-sheng-fu-in-php)
  - [GitHub - uzulla/xhprof-flamegraphs](https://github.com/uzulla/xhprof-flamegraphs)
- [時間を気にせず普通にカンニングもしつつ ISUCON12 本選問題を PHP でやってみる - Speaker Deck](https://speakerdeck.com/sji/shi-jian-woqi-nisezupu-tong-nikanningumositutu-isucon12-ben-xuan-wen-ti-wo-php-deyatutemiru)
  - [GitHub - sj-i/isucon12f-phperkaigi2023-repo](https://github.com/sj-i/isucon12f-phperkaigi2023-repo)
- [GitHub - reliforp/reli-prof](https://github.com/reliforp/reli-prof)
  - [Reli を使った PHP 7.x/8.x サービスの計測｜技術ブログ｜北海道札幌市・宮城県仙台市のVR・ゲーム・システム開発 インフィニットループ](https://www.infiniteloop.co.jp/tech-blog/2023/03/profiling-php8-using-reli/)

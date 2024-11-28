# private-isu-php

[catatsuy/private-isu](https://github.com/catatsuy/private-isu) をPHPで解いた時のログです。

## 環境

README記載の競技者用 (Ubuntu 24.04)のAMIを利用。
x86_64, c7a.large

ベンチマーカーインスタンスも同様。
x86_64, c7a.xlarge

## 事前準備

競技用インスタンスにsshする。

```sh
ssh -i <.pemファイル> ubuntu@<Public IPv4 address>
```

isuconユーザに切り替えて、ホームディレクトリに移動

```sh
sudo su isucon
cd ~
```

### 初期データの準備

```sh
mkdir private_isu/webapp/sq
cd private_isu/
make init
```

MySQLコマンドへデータを流し込む (数分かかる)。

```sh
bunzip2 -c webapp/sql/dump.sql.bz2 | mysql -uisuconp -pisuconp
```

### 鍵の登録、~/.ssh/configの作成

`~/.ssh/authorized_keys` ファイルを作成する。isuconユーザに対して権限を付与する。

```sh
sudo mkdir ~/.ssh
sudo touch ~/.ssh/authorized_keys
sudo chown -R isucon:isucon ~/.ssh
```

GitHubの鍵を登録。

```sh
curl https://github.com/<あなたのGitHubユーザID>.keys >> ~/.ssh/authorized_keys
```

ログアウトし、pemファイルなしでログインできることを確認する。

```sh
ssh isucon@<Public IPv4 addressの値>
```

ベンチマーク用インスタンスでも同様の作業を実施する。

```sh
# ローカル
ssh -i <.pemファイル> ubuntu@<Public IPv4 address>

# サーバ上
sudo su isucon
cd ~
sudo mkdir ~/.ssh
sudo touch ~/.ssh/authorized_keys
sudo chown -R isucon:isucon ~/.ssh
curl https://github.com/<あなたのGitHubユーザID>.keys >> ~/.ssh/authorized_keys

# ローカルで確認
ssh isucon@<Public IPv4 addressの値>
```

必要な設定が終わったので、 `~/.ssh/config` を手元向けに作成する。中身は以下のような形（IPアドレスの値は実際の値と置き換える）。

```sh
Host isu01
  User isucon
  HostName <Public IPv4 addressの値>
Host isubench
  User isucon
  HostName <Public IPv4 addressの値>
```

設定が終わったら以下のようにssh可能になる。

```sh
ssh isu01
ssh isubench
```

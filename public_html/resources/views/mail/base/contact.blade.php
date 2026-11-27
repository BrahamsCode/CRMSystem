{{ \App\Mail\Base\ContactMail::FROM_NAME }}より送信がありました。
━━━━━━━━━━━【送信内容】━━━━━━━━━━━
■送信日時：　{{ $sendDate }}
■ドメイン：　{!! $data['domain'] !!}
■会社名/屋号　　 ：　{!! $data['kaishamei'] !!}
■店舗名　　　　　：　{!! $data['shop_name'] !!}
■担当者名　　　　：　{!! $data['tantou_name'] !!}
■担当者名(カナ)　：　{!! $data['tantou_name_kana'] !!}
■お問い合わせ内容：　{!! $data['type'] !!}
■本文：
{!! $data['comment'] !!}
━━━━━━━━━━━━━━━━━━━━━━━━━━━━

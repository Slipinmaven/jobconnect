<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>JobConnect — Connect to Opportunities Faster</title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,700;0,900;1,700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
<?php
$scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$assetsBase = rtrim($_ENV['APP_URL'] ?? "{$scheme}://{$host}", '/');
$landingPath = '/uploads/landing_images';

function landingImageUrl(string $file, string $base, string $path): string {
    $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
    $localFile = $docRoot . $path . '/' . $file;
    if ($docRoot && file_exists($localFile)) {
        return $base . $path . '/' . $file;
    }
    return $base . $path . '/' . $file; // Always return the uploads path, whether file exists or not
}

$heroImage = landingImageUrl('opportunity.jpg', $assetsBase, $landingPath);
$photobandMain = landingImageUrl('team1.jpg', $assetsBase, $landingPath);
$photobandSide1 = landingImageUrl('team2.jpg', $assetsBase, $landingPath);
$photobandSide2 = landingImageUrl('interview1.jpg', $assetsBase, $landingPath);
?>
<style>
:root{
  --ink:#0c1117;--paper:#f6f4ef;--cream:#edeae1;--slate:#3d4a5c;--muted:#6b7585;
  --accent:#1b6b45;--accent-light:#d1fae5;--accent2:#d95f3b;--accent3:#e8a040;
  --card:#ffffff;--border:rgba(12,17,23,.09);--radius:18px;
  --fh:'Fraunces',Georgia,serif;--fb:'Plus Jakarta Sans',sans-serif;
  --ease:cubic-bezier(.16,1,.3,1);
}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
html{scroll-behavior:smooth}
body{font-family:var(--fb);background:var(--paper);color:var(--ink);line-height:1.65;overflow-x:hidden}
img{display:block;max-width:100%}
a{text-decoration:none}

/* ── NAV ── */
nav{
  position:fixed;top:0;left:0;right:0;z-index:999;
  display:flex;align-items:center;justify-content:space-between;
  padding:1rem 5%;background:rgba(246,244,239,.9);
  backdrop-filter:blur(18px);border-bottom:1px solid var(--border);
  transition:box-shadow .3s;
}
.nav-logo{font-family:var(--fh);font-weight:900;font-size:1.45rem;color:var(--ink);letter-spacing:-.01em}
.nav-logo span{color:var(--accent)}
.nav-links{display:flex;gap:2.2rem;list-style:none;align-items:center}
.nav-links a{font-size:.92rem;color:var(--slate);font-weight:600;transition:color .2s}
.nav-links a:hover{color:var(--accent)}
.nav-cta{background:var(--ink);color:#fff;padding:.55rem 1.4rem;border-radius:50px;font-weight:700;font-size:.88rem;transition:background .2s,transform .2s}
.nav-cta:hover{background:var(--accent);transform:translateY(-1px)}
.hamburger{display:none;cursor:pointer;flex-direction:column;gap:5px;background:none;border:none;padding:.25rem}
.hamburger span{display:block;width:24px;height:2px;background:var(--ink);border-radius:2px;transition:.3s}
.hamburger.open span:nth-child(1){transform:translateY(7px) rotate(45deg)}
.hamburger.open span:nth-child(2){opacity:0}
.hamburger.open span:nth-child(3){transform:translateY(-7px) rotate(-45deg)}

/* ── HERO ── */
#hero{
  min-height:100vh;padding:8rem 5% 5rem;
  display:grid;grid-template-columns:1fr 1fr;gap:3rem;align-items:center;
  position:relative;overflow:hidden;
}
.hero-bg{
  position:absolute;inset:0;z-index:0;
  background:radial-gradient(ellipse 70% 55% at 72% 38%,rgba(27,107,69,.08) 0%,transparent 70%),
             radial-gradient(ellipse 55% 45% at 18% 80%,rgba(217,95,59,.06) 0%,transparent 60%);
}
.hero-dots{
  position:absolute;inset:0;z-index:0;
  background-image:radial-gradient(var(--border) 1.2px,transparent 1.2px);
  background-size:28px 28px;opacity:.55;
}

@keyframes fadeInUp{from{opacity:0;transform:translate3d(0,20px,0)}to{opacity:1;transform:translate3d(0,0,0)}}
@keyframes pop{from{opacity:0;transform:scale(.94)}to{opacity:1;transform:scale(1)}}

.reveal{opacity:0;transform:translateY(14px);will-change:opacity,transform}
.reveal.in-view{animation:fadeInUp .72s var(--ease) forwards}
.reveal.in-view.delay-1{animation-delay:.16s}
.reveal.in-view.delay-2{animation-delay:.32s}
.reveal.in-view.delay-3{animation-delay:.48s}

.hero-content, .pb-in, #features .fc, .trust-in, #showcase .mc, #cta .cta-in{transition:transform .3s var(--ease), opacity .3s var(--ease);}
.hero-content, #features .fc, #showcase .mc, .pb-main, .pb-sc{will-change:transform,opacity}

#hero .hero-content{animation:pop .8s var(--ease) both}
#photoband .pb-in{animation:fadeInUp .85s .12s var(--ease) both}
#features .fc{animation:fadeInUp .65s var(--ease) both}
#features .fc:nth-child(1){animation-delay:.08s}
#features .fc:nth-child(2){animation-delay:.16s}
#features .fc:nth-child(3){animation-delay:.24s}
#features .fc:nth-child(4){animation-delay:.32s}
#features .fc:nth-child(5){animation-delay:.40s}
#features .fc:nth-child(6){animation-delay:.48s}

@media (prefers-reduced-motion: reduce){
  *,*::before,*::after{animation:none!important;transition:none!important;scroll-behavior:auto}
  .hero-visual,.pb-in,#features .fc,#showcase .mc{transform:none!important;opacity:1!important}
}

.hero-content{position:relative;z-index:1}
.hero-tag{
  display:inline-flex;align-items:center;gap:.55rem;
  background:var(--cream);border:1px solid var(--border);
  padding:.38rem 1rem;border-radius:50px;font-size:.8rem;font-weight:700;
  color:var(--accent);margin-bottom:1.8rem;animation:fadeUp .6s var(--ease) both;
}
.htdot{width:7px;height:7px;background:var(--accent);border-radius:50%;animation:pulse 2s infinite}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.3}}
h1{
  font-family:var(--fh);font-weight:900;font-size:clamp(2.6rem,5vw,4.1rem);
  line-height:1.06;letter-spacing:-.02em;margin-bottom:1.2rem;
  animation:fadeUp .6s .1s var(--ease) both;
}
h1 em{font-style:italic;color:var(--accent)}
.udeco{position:relative;display:inline-block}
.udeco::after{content:'';position:absolute;left:0;bottom:-3px;width:100%;height:5px;background:var(--accent2);border-radius:3px}
.hero-sub{font-size:1.1rem;color:var(--slate);max-width:460px;margin-bottom:2.5rem;animation:fadeUp .6s .2s var(--ease) both}
.hero-btns{display:flex;gap:1rem;flex-wrap:wrap;animation:fadeUp .6s .3s var(--ease) both}
.btn-primary{background:var(--ink);color:#fff;padding:.9rem 2rem;border-radius:50px;font-weight:700;font-size:.97rem;display:inline-flex;align-items:center;gap:.55rem;transition:all .25s}
.btn-primary:hover{background:var(--accent);transform:translateY(-2px);box-shadow:0 8px 24px rgba(27,107,69,.25)}
.btn-outline{background:transparent;color:var(--ink);padding:.9rem 2rem;border-radius:50px;font-weight:700;font-size:.97rem;border:2px solid var(--ink);display:inline-flex;align-items:center;gap:.55rem;transition:all .25s}
.btn-outline:hover{border-color:var(--accent);color:var(--accent);transform:translateY(-2px)}

/* floating cards */
.hero-visual{position:relative;z-index:1;animation:fadeUp .6s .15s var(--ease) both}
.floating-cards{position:relative;height:490px}
.fcard{
  position:absolute;background:var(--card);border-radius:var(--radius);
  box-shadow:0 8px 32px rgba(0,0,0,.09);padding:1.2rem 1.4rem;border:1px solid var(--border);
  animation:floatY linear infinite alternate;
}
@keyframes floatY{from{transform:translateY(0) rotate(var(--r,0deg))}to{transform:translateY(-14px) rotate(var(--r,0deg))}}
.fc1{width:265px;top:18px;left:10px;animation-duration:4s;--r:-2deg}
.fc2{width:240px;top:130px;right:5px;animation-duration:5.2s;animation-delay:.8s;--r:2deg}
.fc3{width:230px;bottom:55px;left:45px;animation-duration:3.7s;animation-delay:.4s;--r:1deg}
.fcard-h{display:flex;align-items:center;gap:.7rem;margin-bottom:.8rem}
.av{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:800;color:#fff}
.av1{background:var(--accent)}.av2{background:var(--accent2)}.av3{background:#6366f1}
.fn{font-family:var(--fh);font-weight:700;font-size:.9rem}
.fr{font-size:.75rem;color:var(--muted)}
.ftag{display:inline-block;font-size:.7rem;font-weight:700;padding:.22rem .55rem;border-radius:6px;margin:.2rem .2rem 0 0}
.tg{background:#d1fae5;color:#065f46}.to{background:#ffedd5;color:#7c2d12}.tb{background:#dbeafe;color:#1e3a5f}
.fstatus{display:flex;align-items:center;gap:.4rem;font-size:.77rem;color:var(--accent);font-weight:700;background:#d1fae5;padding:.28rem .65rem;border-radius:6px;margin-top:.6rem;width:fit-content}
.sdot{width:6px;height:6px;background:var(--accent);border-radius:50%}
.fco{display:flex;align-items:center;gap:.5rem;margin-bottom:.5rem}
.clog{width:32px;height:32px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:900;color:#fff}
.cl1{background:#0f172a}.cl2{background:var(--accent2)}
.fjob{font-family:var(--fh);font-weight:700;font-size:.88rem;margin-bottom:.3rem}
.fmeta{font-size:.74rem;color:var(--muted);display:flex;gap:.8rem}
.fsal{font-weight:700;color:var(--accent);font-size:.9rem;margin-top:.4rem}

/* ── TRUST ── */
#trust{background:var(--cream);padding:1.8rem 5%;border-top:1px solid var(--border);border-bottom:1px solid var(--border)}
.trust-in{max-width:1100px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1.5rem}
.tlabel{font-size:.8rem;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.08em;white-space:nowrap}
.tstats{display:flex;gap:3rem;flex-wrap:wrap}
.tstat strong{display:block;font-family:var(--fh);font-weight:900;font-size:1.65rem;color:var(--ink);line-height:1}
.tstat span{font-size:.78rem;color:var(--muted)}

/* ── SECTION BASE ── */
section{padding:6rem 5%}
.inner{max-width:1100px;margin:0 auto}
.sl{display:inline-flex;align-items:center;gap:.5rem;font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.1em;color:var(--accent);margin-bottom:1rem}
.sl::before{content:'';width:18px;height:2px;background:var(--accent);border-radius:2px}
h2{font-family:var(--fh);font-weight:900;font-size:clamp(1.9rem,3.5vw,2.9rem);letter-spacing:-.02em;line-height:1.1;margin-bottom:.8rem}
.ssub{color:var(--slate);font-size:1.03rem;max-width:500px}

/* ── PHOTO BAND ── */
#photoband{padding:3rem 5%;background:var(--paper)}
.pb-in{max-width:1100px;margin:0 auto;display:grid;grid-template-columns:1.15fr .85fr;gap:1.4rem;align-items:stretch}
.pb-main{border-radius:var(--radius);overflow:hidden;position:relative;min-height:340px}
.pb-main img{width:100%;height:100%;object-fit:cover}
.pb-ov{position:absolute;inset:0;background:linear-gradient(to top,rgba(12,17,23,.72) 0%,transparent 55%);display:flex;flex-direction:column;justify-content:flex-end;padding:2rem}
.pb-ov h3{font-family:var(--fh);font-weight:900;font-size:1.55rem;color:#fff;line-height:1.15}
.pb-ov p{font-size:.86rem;color:rgba(255,255,255,.72);margin-top:.4rem}
.pb-side{display:grid;grid-template-rows:1fr 1fr;gap:1.4rem}
.pb-sc{border-radius:var(--radius);overflow:hidden;position:relative;min-height:158px}
.pb-sc img{width:100%;height:100%;object-fit:cover}
.pb-sc .pb-ov{padding:1.2rem}
.pb-sc .pb-ov h3{font-size:1.1rem}

/* ── HOW IT WORKS ── */
#how{background:var(--ink);color:#fff;position:relative;overflow:hidden}
#how .sl{color:var(--accent3)}
#how .sl::before{background:var(--accent3)}
#how h2{color:#fff}
#how .ssub{color:rgba(255,255,255,.6)}
.how-grid{display:grid;grid-template-columns:1fr 1px 1fr;gap:0 3.5rem;margin-top:4rem}
.how-div{background:linear-gradient(to bottom,transparent,rgba(255,255,255,.12),transparent)}
.how-col h3{font-family:var(--fh);font-size:1.1rem;font-weight:700;display:flex;align-items:center;gap:.6rem;margin-bottom:2rem}
.hbadge{width:30px;height:30px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:800;color:var(--ink)}
.step{display:flex;gap:1.2rem;margin-bottom:1.8rem}
.snum{width:40px;height:40px;border-radius:12px;border:2px solid rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;font-family:var(--fh);font-weight:900;font-size:.9rem;color:#fff;flex-shrink:0;transition:.25s}
.step:hover .snum{background:var(--accent);border-color:var(--accent)}
.emp .step:hover .snum{background:var(--accent2);border-color:var(--accent2)}
.sbody strong{display:block;font-family:var(--fh);font-weight:700;margin-bottom:.25rem;color:#fff}
.sbody p{font-size:.87rem;color:rgba(255,255,255,.52);line-height:1.6}

/* ── FEATURES ── */
#features{background:var(--paper)}
.feat-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:1.4rem;margin-top:3.5rem}
.fc{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:1.9rem 1.7rem;transition:all .3s var(--ease);position:relative;overflow:hidden}
.fc::after{content:'';position:absolute;bottom:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--accent),var(--accent3));transform:scaleX(0);transform-origin:left;transition:.3s var(--ease)}
.fc:hover{transform:translateY(-6px);box-shadow:0 20px 48px rgba(0,0,0,.09)}
.fc:hover::after{transform:scaleX(1)}
.fi{width:50px;height:50px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.25rem;margin-bottom:1.2rem}
.fi1{background:#d1fae5;color:#065f46}.fi2{background:#dbeafe;color:#1e3a8a}
.fi3{background:#ede9fe;color:#4c1d95}.fi4{background:#ffedd5;color:#7c2d12}
.fi5{background:#fce7f3;color:#831843}.fi6{background:#f0fdf4;color:#14532d}
.fc h4{font-family:var(--fh);font-weight:700;font-size:1.05rem;margin-bottom:.45rem}
.fc p{font-size:.87rem;color:var(--muted);line-height:1.65}

/* ── WHY ── */
#why{background:var(--cream);display:grid;grid-template-columns:1fr 1fr;gap:5rem;align-items:center}
.why-list{margin-top:2.5rem}
.wi{display:flex;gap:1.2rem;align-items:flex-start;margin-bottom:1.3rem;padding:1.4rem;background:var(--card);border-radius:var(--radius);border:1px solid var(--border);transition:all .25s}
.wi:hover{transform:translateX(6px);border-color:var(--accent);box-shadow:0 4px 16px rgba(27,107,69,.1)}
.wico{width:42px;height:42px;border-radius:12px;background:var(--accent-light);display:flex;align-items:center;justify-content:center;color:var(--accent);font-size:1.1rem;flex-shrink:0}
.wi strong{display:block;font-family:var(--fh);font-weight:700;margin-bottom:.25rem}
.wi p{font-size:.87rem;color:var(--muted)}
.stat-stack{display:flex;flex-direction:column;gap:1.2rem}
.scard{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:1.6rem 2rem;display:flex;align-items:center;gap:1.2rem;box-shadow:0 4px 16px rgba(0,0,0,.05)}
.scard:nth-child(2){margin-left:2rem}
.scard:nth-child(3){margin-left:1rem}
.sico{width:48px;height:48px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0}
.snum2{font-family:var(--fh);font-weight:900;font-size:2.1rem;color:var(--ink);line-height:1}
.snum2 sup{font-size:1.1rem;color:var(--accent)}
.sdesc{font-size:.82rem;color:var(--muted);margin-top:.2rem}

/* ── SHOWCASE ── */
#showcase{background:var(--ink);overflow:hidden}
#showcase h2{color:#fff}
#showcase .sl{color:var(--accent3)}
#showcase .sl::before{background:var(--accent3)}
#showcase .ssub{color:rgba(255,255,255,.55)}
.sc-scroll{display:flex;gap:1.5rem;margin-top:3rem;overflow-x:auto;padding-bottom:1.2rem;scrollbar-width:none}
.sc-scroll::-webkit-scrollbar{display:none}
.mc{flex-shrink:0;background:#161e2e;border:1px solid rgba(255,255,255,.08);border-radius:var(--radius);overflow:hidden;width:310px;transition:.3s}
.mc:hover{transform:translateY(-6px);border-color:rgba(27,107,69,.5)}
.mh{background:linear-gradient(135deg,var(--accent),#40916c);padding:1.2rem 1.4rem}
.mh2{background:linear-gradient(135deg,#6366f1,#8b5cf6)}
.mh3{background:linear-gradient(135deg,var(--accent2),var(--accent3))}
.mht{color:#fff;font-family:var(--fh);font-weight:700;font-size:.95rem;display:flex;align-items:center;gap:.5rem}
.mhs{color:rgba(255,255,255,.7);font-size:.75rem;margin-top:.2rem}
.mb2{padding:1.3rem}
.mr{display:flex;align-items:center;justify-content:space-between;padding:.6rem 0;border-bottom:1px solid rgba(255,255,255,.06)}
.mr:last-child{border-bottom:none}
.ml{font-size:.77rem;color:rgba(255,255,255,.45)}
.mv{font-size:.8rem;color:rgba(255,255,255,.85);font-weight:600}
.mp{display:inline-block;font-size:.7rem;font-weight:700;padding:.22rem .6rem;border-radius:20px}
.pg{background:rgba(27,107,69,.3);color:#6ee7b7}.py{background:rgba(232,160,64,.2);color:var(--accent3)}
.pb{background:rgba(99,102,241,.25);color:#a5b4fc}.pr{background:rgba(217,95,59,.2);color:#fca5a5}

/* ── CTA ── */
#cta{background:var(--paper)}
.cta-in{background:var(--ink);border-radius:28px;padding:5.5rem 3rem;position:relative;overflow:hidden;text-align:center}
.cglow1{position:absolute;width:400px;height:400px;border-radius:50%;background:radial-gradient(circle,rgba(27,107,69,.4) 0%,transparent 70%);top:-100px;right:-80px;pointer-events:none}
.cglow2{position:absolute;width:320px;height:320px;border-radius:50%;background:radial-gradient(circle,rgba(217,95,59,.25) 0%,transparent 70%);bottom:-80px;left:-80px;pointer-events:none}
#cta h2{color:#fff;font-size:clamp(2.1rem,4vw,3.2rem);position:relative;z-index:1}
#cta p{color:rgba(255,255,255,.6);font-size:1.08rem;margin:.8rem 0 2.5rem;position:relative;z-index:1}
.cta-btns{display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;position:relative;z-index:1}
.bw{background:#fff;color:var(--ink);padding:.9rem 2.2rem;border-radius:50px;font-weight:700;font-size:.97rem;transition:all .25s;display:inline-flex;align-items:center;gap:.5rem}
.bw:hover{background:var(--accent);color:#fff;transform:translateY(-2px)}
.bg2{background:transparent;color:#fff;padding:.9rem 2.2rem;border-radius:50px;font-weight:700;font-size:.97rem;border:2px solid rgba(255,255,255,.28);transition:all .25s;display:inline-flex;align-items:center;gap:.5rem}
.bg2:hover{border-color:#fff;background:rgba(255,255,255,.08);transform:translateY(-2px)}

/* ── FOOTER ── */
footer{background:#070c12;color:rgba(255,255,255,.5);padding:4.5rem 5% 0;font-size:.9rem}
.ft{max-width:1100px;margin:0 auto;display:grid;grid-template-columns:1.6fr 1fr 1fr 1.5fr;gap:3rem;padding-bottom:3rem;border-bottom:1px solid rgba(255,255,255,.08)}
.flogo{font-family:var(--fh);font-weight:900;font-size:1.5rem;color:#fff;margin-bottom:.8rem}
.flogo span{color:var(--accent)}
.ftag2{font-size:.87rem;color:rgba(255,255,255,.42);line-height:1.65;max-width:240px;margin-bottom:1.4rem}
.fsoc{display:flex;gap:.75rem}
.sbtn{width:36px;height:36px;border-radius:10px;background:rgba(255,255,255,.07);display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,.45);font-size:.9rem;transition:all .2s}
.sbtn:hover{background:var(--accent);color:#fff;transform:translateY(-2px)}
.fcol h5{font-family:var(--fh);font-weight:700;font-size:.95rem;color:#fff;margin-bottom:1.3rem;letter-spacing:-.01em}
.fcol ul{list-style:none}
.fcol ul li{margin-bottom:.75rem}
.fcol ul li a{color:rgba(255,255,255,.42);transition:color .2s;font-size:.87rem;display:inline-flex;align-items:center;gap:.4rem}
.fcol ul li a:hover{color:#fff}
.fcol ul li a i{font-size:.72rem}
.ci{display:flex;align-items:flex-start;gap:.85rem;margin-bottom:1.1rem}
.cico{width:34px;height:34px;border-radius:10px;background:rgba(27,107,69,.2);display:flex;align-items:center;justify-content:center;color:var(--accent);font-size:.85rem;flex-shrink:0;margin-top:.05rem}
.ci p{font-size:.8rem;color:rgba(255,255,255,.38);margin-bottom:.1rem}
.ci a,.ci span{color:rgba(255,255,255,.75);font-size:.87rem;font-weight:600;word-break:break-word}
.ci a:hover{color:var(--accent)}
.fb{max-width:1100px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;padding:1.5rem 0}
.fb p{font-size:.8rem;color:rgba(255,255,255,.28)}
.fbadges{display:flex;gap:.8rem}
.fpill{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.08);border-radius:50px;padding:.3rem .85rem;font-size:.74rem;color:rgba(255,255,255,.32);display:inline-flex;align-items:center;gap:.35rem}
.fpill i{color:var(--accent)}

/* ── ANIMATIONS ── */
@keyframes fadeUp{from{opacity:0;transform:translateY(24px)}to{opacity:1;transform:translateY(0)}}
.reveal{opacity:0;transform:translateY(30px);transition:opacity .7s var(--ease),transform .7s var(--ease)}
.reveal.visible{opacity:1;transform:translateY(0)}
.d1{transition-delay:.1s}.d2{transition-delay:.2s}.d3{transition-delay:.3s}.d4{transition-delay:.4s}

/* ── RESPONSIVE ── */
@media(max-width:1024px){.ft{grid-template-columns:1fr 1fr}}
@media(max-width:900px){
  #hero{grid-template-columns:1fr;padding-top:7rem}
  .hero-visual{display:none}
  .how-grid{grid-template-columns:1fr;gap:3rem}
  .how-div{display:none}
  .feat-grid{grid-template-columns:repeat(2,1fr)}
  #why{grid-template-columns:1fr;gap:3rem}
  .pb-in{grid-template-columns:1fr}
  .pb-side{grid-template-rows:unset;grid-template-columns:1fr 1fr}
}
@media(max-width:640px){
  section{padding:4.5rem 5%}
  .feat-grid{grid-template-columns:1fr}
  .tstats{gap:1.5rem}
  .ft{grid-template-columns:1fr;gap:2.5rem}
  .fb{flex-direction:column;align-items:flex-start}
  .cta-in{padding:3.5rem 1.5rem}
  .pb-side{grid-template-columns:1fr}
  .scard:nth-child(2),.scard:nth-child(3){margin-left:0}
  .nav-links{
    display:none;flex-direction:column;position:fixed;
    top:62px;left:0;right:0;background:var(--paper);
    padding:2rem 5%;border-bottom:1px solid var(--border);
    box-shadow:0 8px 24px rgba(0,0,0,.08);gap:1.5rem;
  }
  .nav-links.open{display:flex}
  .hamburger{display:flex}
  nav .nav-cta{display:none}
}
@media(max-width:400px){h1{font-size:2.3rem}}
</style>
</head>
<body>

<!-- NAV -->
<nav id="mainNav">
  <a href="#" class="nav-logo">Job<span>Connect</span></a>
  <ul class="nav-links" id="navLinks">
    <li><a href="#how">How It Works</a></li>
    <li><a href="#features">Features</a></li>
    <li><a href="#why">Why Us</a></li>
    <li><a href="#footerContact">Contact</a></li>
    <li><a href="/login" style="color:var(--accent);font-weight:700">Login</a></li>
  </ul>
  <a href="/register" class="nav-cta">Get Started &nbsp;<i class="fa-solid fa-arrow-right"></i></a>
  <button class="hamburger" id="ham" aria-label="Menu">
    <span></span><span></span><span></span>
  </button>
</nav>

<!-- HERO -->
<section id="hero">
  <div class="hero-bg"></div>
  <div class="hero-dots"></div>
  <div class="hero-content">
    <div class="hero-tag">
      <span class="htdot"></span>
      <i class="fa-solid fa-location-dot"></i> Now live in 40+ cities across Africa
    </div>
    <h1>Connect to<br><em>Opportunities</em><br><span class="udeco">Faster</span></h1>
    <p class="hero-sub">JobConnect helps you find jobs and hire talent without the noise — clear, direct, and built for real results.</p>
    <div class="hero-btns">
      <a href="/register?role=jobseeker" class="btn-primary"><i class="fa-solid fa-user-tie"></i> Find a Job</a>
      <a href="/register?role=employer" class="btn-outline"><i class="fa-solid fa-briefcase"></i> Post a Job</a>
    </div>
  </div>
  <div class="hero-visual">
    <div class="floating-cards">
      <div class="fcard fc1">
        <div class="fcard-h">
          <div class="av av1">AN</div>
          <div><div class="fn">Amara Ndiaye</div><div class="fr">Senior Product Designer</div></div>
        </div>
        <div>
          <span class="ftag tg">UI/UX</span>
          <span class="ftag tb">Figma</span>
          <span class="ftag to">4 yrs exp</span>
        </div>
        <div class="fstatus"><span class="sdot"></span><i class="fa-solid fa-circle-check" style="font-size:.7rem"></i> Open to work</div>
      </div>
      <div class="fcard fc2">
        <div class="fco">
          <div class="clog cl1">N</div>
          <div><div class="fn">Nile Tech</div><div class="fr"><i class="fa-solid fa-location-dot" style="font-size:.68rem"></i> Nairobi · Remote</div></div>
        </div>
        <div class="fjob">Frontend Engineer</div>
        <div class="fmeta"><span><i class="fa-regular fa-clock"></i> Full-time</span><span><i class="fa-solid fa-layer-group"></i> Mid-level</span></div>
        <div class="fsal"><i class="fa-solid fa-coins" style="font-size:.76rem"></i> KES 180k – 240k/mo</div>
      </div>
      <div class="fcard fc3">
        <div class="fco">
          <div class="clog cl2">S</div>
          <div><div class="fn">Sahara Digital</div><div class="fr"><i class="fa-solid fa-location-dot" style="font-size:.68rem"></i> Lagos, Nigeria</div></div>
        </div>
        <div class="fjob">Data Analyst</div>
        <div class="fstatus" style="background:#dbeafe;color:#1e40af;margin-top:.65rem">
          <span class="sdot" style="background:#3b82f6"></span>
          <i class="fa-solid fa-video" style="font-size:.7rem"></i> Interview link sent
        </div>
      </div>
    </div>
  </div>
</section>

<!-- TRUST BAR -->
<div id="trust">
  <div class="trust-in">
    <span class="tlabel"><i class="fa-solid fa-chart-simple"></i> &nbsp;Platform stats</span>
    <div class="tstats">
      <div class="tstat"><strong>12k+</strong><span>Job Seekers</span></div>
      <div class="tstat"><strong>3.4k+</strong><span>Jobs / Month</span></div>
      <div class="tstat"><strong>89%</strong><span>Shortlisted in 48h</span></div>
      <div class="tstat"><strong>40+</strong><span>Cities</span></div>
    </div>
  </div>
</div>

<!-- PHOTO BAND -->
<div id="photoband">
  <div class="pb-in reveal">
    <div class="pb-main">
      <img src="<?= htmlspecialchars($photobandMain, ENT_QUOTES, 'UTF-8') ?>" alt="Professionals collaborating" loading="lazy" />
      <div class="pb-ov">
        <h3>Where Careers Begin &amp; Teams Grow</h3>
        <p>Thousands of professionals and companies connect on JobConnect every month.</p>
      </div>
    </div>
    <div class="pb-side">
      <div class="pb-sc">
        <img src="<?= htmlspecialchars($photobandSide1, ENT_QUOTES, 'UTF-8') ?>" alt="Job seeker reviewing opportunities" loading="lazy" />
        <div class="pb-ov"><h3>Find Your Next Role</h3><p>Browse curated listings matched to your skills.</p></div>
      </div>
      <div class="pb-sc">
        <img src="<?= htmlspecialchars($photobandSide2, ENT_QUOTES, 'UTF-8') ?>" alt="Employer reviewing candidates" loading="lazy" />
        <div class="pb-ov"><h3>Hire with Confidence</h3><p>Review real profiles and shortlist fast.</p></div>
      </div>
    </div>
  </div>
</div>

<!-- HOW IT WORKS -->
<section id="how">
  <div class="inner">
    <div class="sl">How it works</div>
    <h2>Two paths, one platform</h2>
    <p class="ssub">Whether you're hiring or job hunting, the flow is simple and fast.</p>
    <div class="how-grid reveal">
      <div class="how-col">
        <h3 style="color:var(--accent3)">
          <span class="hbadge" style="background:var(--accent3)"><i class="fa-solid fa-user"></i></span>
          For Job Seekers
        </h3>
        <div class="step"><div class="snum">01</div><div class="sbody"><strong><i class="fa-solid fa-id-card"></i> Create your profile</strong><p>Add your skills, experience, and goals. Ready in under 5 minutes.</p></div></div>
        <div class="step"><div class="snum">02</div><div class="sbody"><strong><i class="fa-solid fa-magnifying-glass"></i> Browse &amp; apply</strong><p>Explore curated listings matched to your profile. One-click applications.</p></div></div>
        <div class="step"><div class="snum">03</div><div class="sbody"><strong><i class="fa-solid fa-star"></i> Get shortlisted</strong><p>Employers review and mark you shortlisted when you're a match.</p></div></div>
        <div class="step"><div class="snum">04</div><div class="sbody"><strong><i class="fa-brands fa-google"></i> Receive interview link</strong><p>A Google Meet link lands in your email. Just show up.</p></div></div>
      </div>
      <div class="how-div"></div>
      <div class="how-col emp">
        <h3 style="color:var(--accent2)">
          <span class="hbadge" style="background:var(--accent2)"><i class="fa-solid fa-building"></i></span>
          For Employers
        </h3>
        <div class="step"><div class="snum" style="border-color:rgba(217,95,59,.3);color:var(--accent2)">01</div><div class="sbody"><strong><i class="fa-solid fa-pen-to-square"></i> Post a job</strong><p>Describe the role, salary, and requirements. Go live immediately.</p></div></div>
        <div class="step"><div class="snum" style="border-color:rgba(217,95,59,.3);color:var(--accent2)">02</div><div class="sbody"><strong><i class="fa-solid fa-inbox"></i> Receive applications</strong><p>Candidates apply directly. All submissions are organised and ready to review.</p></div></div>
        <div class="step"><div class="snum" style="border-color:rgba(217,95,59,.3);color:var(--accent2)">03</div><div class="sbody"><strong><i class="fa-solid fa-list-check"></i> Shortlist candidates</strong><p>Review profiles at a glance and shortlist the right people in one click.</p></div></div>
        <div class="step"><div class="snum" style="border-color:rgba(217,95,59,.3);color:var(--accent2)">04</div><div class="sbody"><strong><i class="fa-solid fa-video"></i> Send interview link</strong><p>Share your meeting link with shortlisted candidates. That's the whole process.</p></div></div>
      </div>
    </div>
  </div>
</section>

<!-- FEATURES -->
<section id="features">
  <div class="inner">
    <div class="sl">Platform features</div>
    <h2>Everything you actually need</h2>
    <p class="ssub">No bloat, no unnecessary complexity — just what moves hiring forward.</p>
    <div class="feat-grid">
      <div class="fc reveal"><div class="fi fi1"><i class="fa-solid fa-bullseye"></i></div><h4>Smart Job Matching</h4><p>Candidates see jobs relevant to their skills. Employers get applicants who actually fit the role.</p></div>
      <div class="fc reveal d1"><div class="fi fi2"><i class="fa-solid fa-chart-bar"></i></div><h4>Application Tracking</h4><p>Track every application in one clean dashboard. Know exactly where each candidate stands.</p></div>
      <div class="fc reveal d2"><div class="fi fi3"><i class="fa-solid fa-table-columns"></i></div><h4>Employer Dashboard</h4><p>A focused view of all active roles, applicants, and shortlisted candidates — nothing more.</p></div>
      <div class="fc reveal d1"><div class="fi fi4"><i class="fa-solid fa-bolt"></i></div><h4>Fast Shortlisting</h4><p>Skim profiles quickly and shortlist in one click. Built for speed, not bureaucracy.</p></div>
      <div class="fc reveal d2"><div class="fi fi5"><i class="fa-solid fa-calendar-check"></i></div><h4>External Interview Integration</h4><p>Paste your Google Meet or any video link. Candidates get notified instantly.</p></div>
      <div class="fc reveal d3"><div class="fi fi6"><i class="fa-solid fa-bell"></i></div><h4>Real-time Notifications</h4><p>Never miss an update — new applicants, shortlists, and interview links trigger instant alerts.</p></div>
    </div>
  </div>
</section>

<!-- WHY -->
<section id="why">
  <div class="reveal">
    <div class="sl">Why JobConnect</div>
    <h2>Hiring should be<br><em style="font-style:italic;color:var(--accent)">simple</em> again</h2>
    <p class="ssub">We stripped away everything that slows you down and kept only what gets results.</p>
    <div class="why-list">
      <div class="wi reveal"><div class="wico"><i class="fa-solid fa-ban"></i></div><div><strong>No complicated systems</strong><p>Sign up, post or apply, connect. That's the entire flow from start to finish.</p></div></div>
      <div class="wi reveal delay-1"><div class="wico"><i class="fa-solid fa-gauge-high"></i></div><div><strong>Fast hiring process</strong><p>Employers can go from posting to interviewing in under 24 hours.</p></div></div>
      <div class="wi reveal delay-2"><div class="wico"><i class="fa-solid fa-handshake"></i></div><div><strong>Direct &amp; efficient communication</strong><p>Shortlisting is a signal. An interview link is a commitment. No ambiguity.</p></div></div>
      <div class="wi reveal delay-3"><div class="wico"><i class="fa-solid fa-earth-africa"></i></div><div><strong>Designed for real-world use</strong><p>Built with Africa's job market in mind — practical, accessible, and fast on any connection.</p></div></div>
    </div>
  </div>
  <div class="why-vis reveal">
    <div class="stat-stack">
      <div class="scard reveal"><div class="sico" style="background:#d1fae5;color:var(--accent)"><i class="fa-solid fa-users"></i></div><div><div class="snum2">12k<sup>+</sup></div><div class="sdesc">Active job seekers on the platform</div></div></div>
      <div class="scard reveal delay-1"><div class="sico" style="background:#dbeafe;color:#1e3a8a"><i class="fa-solid fa-briefcase"></i></div><div><div class="snum2">3.4k<sup>+</sup></div><div class="sdesc">New jobs posted every month</div></div></div>
      <div class="scard reveal delay-2"><div class="sico" style="background:#ffedd5;color:#7c2d12"><i class="fa-solid fa-stopwatch"></i></div><div><div class="snum2">89<sup>%</sup></div><div class="sdesc">Candidates shortlisted within 48 hours</div></div></div>
    </div>
  </div>
</section>

<!-- SHOWCASE -->
<section id="showcase">
  <div class="inner">
    <div class="sl">Platform preview</div>
    <h2>See what's inside</h2>
    <p class="ssub">A peek at what employers and job seekers experience after signing up.</p>
    <div class="sc-scroll reveal">
      <div class="mc">
        <div class="mh"><div class="mht"><i class="fa-solid fa-list-ul"></i> Active Job Listings</div><div class="mhs">Employer view &mdash; 3 open roles</div></div>
        <div class="mb2">
          <div class="mr"><span class="ml">Frontend Engineer</span><span class="mp pg">24 apps</span></div>
          <div class="mr"><span class="ml">UI/UX Designer</span><span class="mp py">11 apps</span></div>
          <div class="mr"><span class="ml">Data Analyst</span><span class="mp pb">8 apps</span></div>
          <div class="mr"><span class="ml">Total applicants</span><span class="mv">43</span></div>
          <div class="mr"><span class="ml">Shortlisted</span><span class="mv">9</span></div>
        </div>
      </div>
      <div class="mc">
        <div class="mh mh2"><div class="mht"><i class="fa-solid fa-circle-user"></i> Candidate Profile</div><div class="mhs">As seen by employers</div></div>
        <div class="mb2">
          <div class="mr"><span class="ml">Name</span><span class="mv">Kofi Mensah</span></div>
          <div class="mr"><span class="ml">Role</span><span class="mv">Backend Engineer</span></div>
          <div class="mr"><span class="ml">Experience</span><span class="mv">5 years</span></div>
          <div class="mr"><span class="ml">Location</span><span class="mv">Accra, Ghana</span></div>
          <div class="mr"><span class="ml">Status</span><span class="mp pg">Shortlisted</span></div>
        </div>
      </div>
      <div class="mc">
        <div class="mh mh3"><div class="mht"><i class="fa-solid fa-chart-pie"></i> Application Status</div><div class="mhs">Seeker view &mdash; My applications</div></div>
        <div class="mb2">
          <div class="mr"><span class="ml">Nile Tech — FE Eng</span><span class="mp pb">Reviewing</span></div>
          <div class="mr"><span class="ml">Sahara Digital — DA</span><span class="mp pg">Shortlisted</span></div>
          <div class="mr"><span class="ml">Lagos Startup — PM</span><span class="mp py">Applied</span></div>
          <div class="mr"><span class="ml">Kigali Hub — UX</span><span class="mp pr">Not selected</span></div>
          <div class="mr"><span class="ml">Interview link</span><span class="mv" style="color:#6ee7b7"><i class="fa-solid fa-check"></i> Received</span></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- CTA -->
<section id="cta">
  <div class="inner">
    <div class="cta-in reveal">
      <div class="cglow1"></div><div class="cglow2"></div>
      <h2>Start Connecting Today</h2>
      <p>Whether you're looking for your next role or your next great hire — it starts here.</p>
      <div class="cta-btns">
        <a href="/register?role=jobseeker" class="bw"><i class="fa-solid fa-magnifying-glass"></i> Find Jobs</a>
        <a href="/register?role=employer" class="bg2"><i class="fa-solid fa-pen-to-square"></i> Post a Job</a>
      </div>
    </div>
  </div>
</section>

<!-- FOOTER -->
<footer id="footerContact">
  <div class="ft">

    <!-- Brand -->
    <div>
      <div class="flogo">Job<span>Connect</span></div>
      <p class="ftag2">Connecting talent and opportunity across Africa — fast, simple, and built for real results.</p>
      <div class="fsoc">
        <a href="#" class="sbtn" aria-label="X / Twitter"><i class="fa-brands fa-x-twitter"></i></a>
        <a href="#" class="sbtn" aria-label="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
        <a href="#" class="sbtn" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
        <a href="#" class="sbtn" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
      </div>
    </div>

    <!-- Platform -->
    <div class="fcol">
      <h5>Platform</h5>
      <ul>
        <li><a href="#"><i class="fa-solid fa-chevron-right"></i> Browse Jobs</a></li>
        <li><a href="#"><i class="fa-solid fa-chevron-right"></i> Post a Job</a></li>
        <li><a href="#"><i class="fa-solid fa-chevron-right"></i> For Employers</a></li>
        <li><a href="#"><i class="fa-solid fa-chevron-right"></i> For Job Seekers</a></li>
        <li><a href="#"><i class="fa-solid fa-chevron-right"></i> Pricing</a></li>
      </ul>
    </div>

    <!-- Company -->
    <div class="fcol">
      <h5>Company</h5>
      <ul>
        <li><a href="#"><i class="fa-solid fa-chevron-right"></i> About Us</a></li>
        <li><a href="#"><i class="fa-solid fa-chevron-right"></i> Blog</a></li>
        <li><a href="#"><i class="fa-solid fa-chevron-right"></i> Careers</a></li>
        <li><a href="#"><i class="fa-solid fa-chevron-right"></i> Privacy Policy</a></li>
        <li><a href="#"><i class="fa-solid fa-chevron-right"></i> Terms of Service</a></li>
      </ul>
    </div>

    <!-- Contact -->
    <div class="fcol">
      <h5>Get in Touch</h5>
      <div class="ci">
        <div class="cico"><i class="fa-solid fa-envelope"></i></div>
        <div><p>Email us</p><a href="mailto:team.jobconnect@gmail.com">team.jobconnect@gmail.com</a></div>
      </div>
      <div class="ci">
        <div class="cico"><i class="fa-solid fa-phone"></i></div>
        <div><p>Call us</p><span>0110486005</span></div>
      </div>
      <div class="ci">
        <div class="cico"><i class="fa-solid fa-location-dot"></i></div>
        <div><p>Headquarters</p><span>Nairobi, Kenya</span></div>
      </div>
      <div class="ci">
        <div class="cico"><i class="fa-regular fa-clock"></i></div>
        <div><p>Support hours</p><span>Mon – Fri, 8 am – 6 pm EAT</span></div>
      </div>
    </div>

  </div>

  <div class="fb">
    <p>&copy; 2025 JobConnect. All rights reserved. Built for Africa's workforce.</p>
    <div class="fbadges">
      <span class="fpill"><i class="fa-solid fa-shield-halved"></i> Secure &amp; Private</span>
      <span class="fpill"><i class="fa-solid fa-mobile-screen"></i> Mobile Friendly</span>
    </div>
  </div>
</footer>

<script>
const ham = document.getElementById('ham');
const nav = document.getElementById('navLinks');
ham.addEventListener('click', () => { ham.classList.toggle('open'); nav.classList.toggle('open'); });
nav.querySelectorAll('a').forEach(a => a.addEventListener('click', () => { ham.classList.remove('open'); nav.classList.remove('open'); }));

const io = new IntersectionObserver(es => es.forEach(e => { if(e.isIntersecting) e.target.classList.add('in-view'); }), {threshold:.1});
document.querySelectorAll('.reveal').forEach(el => io.observe(el));

window.addEventListener('scroll', () => {
  document.getElementById('mainNav').style.boxShadow = window.scrollY > 24 ? '0 4px 24px rgba(0,0,0,.08)' : 'none';
});
</script>
</body>
</html>
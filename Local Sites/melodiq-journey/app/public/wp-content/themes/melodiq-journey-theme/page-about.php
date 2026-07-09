
<?php
/**
 * Template Name: About Page
 *
 * @package Melodiq_Journey
 */

get_header();
?>

<main class="mj-about-page">
  <div class="container page-shell">
    <div class="mj-about-content">
      <section class="mj-about-hero">
        <div class="mj-about-hero__content">
          <h1>A melodic techno közösségi oldala.</h1>
          <p class="mj-about-hero__lead">
            A Melodiq Journey azoknak szól, akiknek a zene nem háttér, hanem élmény, irány és kapcsolódási pont.
            Eseményeket, artistokat és közösségi lehetőségeket gyűjtünk egy helyre, hogy könnyebb legyen megtalálni
            a következő estét, utazást vagy találkozást.
          </p>
          <div class="mj-about-hero__actions">
            <a href="<?php echo esc_url(melodiq_event_archive_url()); ?>" class="mj-btn mj-btn--primary">Események</a>
            <a href="<?php echo esc_url(home_url('/kapcsolat/')); ?>" class="mj-btn mj-btn--ghost">Kapcsolat</a>
          </div>
        </div>

        <div class="mj-about-hero__card">
          <span class="mj-about-hero__label">Melodiq Journey</span>
          <h2>Akiket ugyanaz a hangzás mozgat.</h2>
          <p>
            Magyarországon épülő melodic techno közösség eseményekkel, artist bio-kkal, hírlevéllel
            és később Journey Club tagsági előnyökkel.
          </p>
        </div>
      </section>

      <section class="mj-about-section mj-about-story">
        <div class="mj-section-heading">
          <p class="mj-eyebrow">A történetünk</p>
          <h2>Egy közös zenei ízlésből indult.</h2>
        </div>
        <p>
          A Melodiq Journey abból az egyszerű felismerésből született, hogy rengetegen keresik ugyanazt az energiát:
          mélyebb dallamokat, erős atmoszférát, minőségi helyszíneket és olyan embereket, akikkel ezt meg lehet osztani.
        </p>
        <p>
          Nem csak programokat szeretnénk mutatni. A célunk, hogy a közösség könnyebben kapcsolódjon eseményekhez,
          artistokhoz és egymáshoz. Egy helyet építünk, ahol a melodic techno köré szerveződő élmények átláthatóak,
          elérhetőek és személyesebbek lesznek.
        </p>
      </section>

      <section class="mj-about-section">
        <div class="mj-section-heading">
          <p class="mj-eyebrow">Mit csinálunk?</p>
          <h2>Összekötjük az eseményeket, az artistokat és a közösséget.</h2>
        </div>

        <div class="mj-about-grid">
          <article class="mj-about-card">
            <span>01</span>
            <h3>Események</h3>
            <p>Kiemelt bulik, fesztiválok és klubestek egy helyen, dátummal, helyszínnel, lineuppal és jegylinkkel.</p>
          </article>

          <article class="mj-about-card">
            <span>02</span>
            <h3>Artist</h3>
            <p>Artist profilok és bio-k, hogy ne csak a nevet lásd a plakáton, hanem a mögötte lévő történetet is.</p>
          </article>

          <article class="mj-about-card">
            <span>03</span>
            <h3>Közösség</h3>
            <p>Hírlevél, közösségi felületek és későbbi tagi funkciók azoknak, akik aktívabban kapcsolódnának.</p>
          </article>

          <article class="mj-about-card">
            <span>04</span>
            <h3>Journey Club</h3>
            <p>A következő lépés: tagsági rendszer kedvezményekkel, előnyökkel és válogatott közösségi élményekkel.</p>
          </article>
        </div>
      </section>

      <section class="mj-about-stats">
        <div>
          <strong>01</strong>
          <span>közösség</span>
        </div>
        <div>
          <strong>04</strong>
          <span>fő terület</span>
        </div>
        <div>
          <strong>24/7</strong>
          <span>eseményfigyelés</span>
        </div>
        <div>
          <strong>CLUB</strong>
          <span>hamarosan</span>
        </div>
      </section>

      <section class="mj-about-section mj-about-values">
        <div class="mj-section-heading">
          <p class="mj-eyebrow">Értékeink</p>
          <h2>Erre épül a Melodiq Journey.</h2>
        </div>

        <ul>
          <li><strong>Kurált élmények.</strong> Nem mindent akarunk megmutatni, hanem azt, ami illik a közösség hangulatához.</li>
          <li><strong>Valódi kapcsolódás.</strong> A zene az indulópont, de az emberek miatt lesz emlékezetes egy este.</li>
          <li><strong>Tiszta információ.</strong> Dátum, helyszín, lineup, jegyvásárlás és artist háttér egy átlátható felületen.</li>
          <li><strong>Hosszú távú építkezés.</strong> Egy olyan platformot építünk, amely később klubtagságra és partneri előnyökre is támaszkodhat.</li>
        </ul>
      </section>

      <section class="mj-about-future">
        <p class="mj-eyebrow">A jövő</p>
        <h2>A Journey Club lesz a következő szint.</h2>
        <a href="<?php echo esc_url(home_url('/fiokom/')); ?>" class="mj-btn mj-btn--primary">Fiók létrehozása</a>
      </section>
    </div>

    <?php get_template_part('template-parts/event-sidebar'); ?>
  </div>
</main>

<?php
get_footer();

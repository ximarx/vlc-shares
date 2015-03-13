# Premesse #

L'obbiettivo di questo tutorial è quello di mostrare, in maniera pratica, come realizzare un nuovo plugin per VLCShares di tipo DataProvider. I plugin di questo tipo hanno come scopo principale quello di navigare all'interno di un sito web e estrappolarne i contenuti video o audio per renderli utilizzabili dai dispositivi supportati da VLCShares.



## Prefazione: note preliminari ##

Nel tutorial utilizzerò queste convenzioni di base per questioni di comodità:
  * **APACHE\_WWW**: indica la directory _DocumentRoot_ utilizzata da Apache. Per Ubuntu, corrisponde alla directory `/var/www/`, per Windows (utilizzando il pacchetto di installazione automatica di VLCShares) corrisponde a `C:\Program Files\VLCShares\www`.
  * **VLCSHARES\_BASEDIR**: indica la directory principale di VLCShares (quella che contiene le cartelle `library`, `application`,...). Per Ubuntu corrisponde a `APACHE_WWW/vlc-shares/`, per Windows `APACHE_WWW\vlc-shares\`.
  * **VLCSHARESDEV\_BASEDIR**: sarà la directory in cui andremo ad installare la versione di sviluppo di vlc-shares. Per Ubuntu corrisponde a `APACHE_WWW/vlc-shares-dev/`, per Windows `APACHE_WWW\vlc-shares-dev\`.

## Prefazione: prepariamo il NOSTRO ambiente di sviluppo ##

In questa fase cercheremo di preparare una installazione di sviluppo di vlc-shares sulla quale poter sviluppare il nostro plugin in maniera diretta. In questo modo potremo lavorare senza dover necessariamente rimuovere e reinstallare ogni versione del plugin per poterne testare le modifiche.

Per poter completare con successo questo tutorial è necessario che siate forniti di qualche semplice strumento di base:
  * **una installazione funzionante di VLCShares.**
  * **un editor di testo o un IDE per php**. Potete usare quello che preferite. Vanno bene sia i semplici editor di testo con il riconoscimento della sintassi php (ad esempio Notepad++ se siete su Windows o GEdit se siete su Linux), che IDE più avanzati che offrono ad esempio auto completamento del codice o altro (ad esempio Zend Studio, Eclipse PDT...)
  * **svn** (se siete su Windows, potete utilizzare TortoiseSVN)
  * **php-cli** (su ubuntu basta installare il pacchetto php5-cli, su Windows dovrebbe essere già compreso nella distribuzione EasyPHP)

Una volta che vi siete procurato il necessario, iniziamo scaricando la versione di sviluppo di vlc-shares.
Utilizzando svn (o TortoiseSVN) dovete eseguire un checkout di una versione di sviluppo all'interno della directory **VLCSHARESDEV\_BASEDIR**. Dovete però decidere quale. Avete due scelte a disposizione:
  * potete utilizzare la versione instabile di sviluppo, nel qual caso l'indirizzo di cui effettuare il checkout è il seguente
```
http://vlc-shares.googlecode.com/svn/trunk/
```
  * oppure potete utilizzare una versione stabile a vostra scelta. Questa guida ha come obbiettivo lo sviluppo di plugin per la versione 0.5.4. Utilizzeremo quindi come indirizzo
```
http://vlc-shares.googlecode.com/svn/tags/0.5.4/
```
**ATTENZIONE:** attualmente la versione 0.5.4 non è stata ancora rilasciata, quindi per ora dovete usare la versione di sviluppo instabile.

Una volta terminato lo scaricamento, aprile il file **VLCSHARESDEV\_BASEDIR**`/public/.htaccess` e aggiungete come prima riga questo testo:

```
SetEnv APPLICATION_ENV development
```

Fatto questo, posizionate il browser all'indirizzo `http://localhost/vlc-shares-dev/public/` e procedete come in una normale installazione di vlc-shares. E' buona norma non installare alcun genere di plugin opzionale, a meno che non lo riteniate un requisito necessario per il vostro plugin.

Una volta completata l'installazione, aprite una console (per Windows XP: cliccate su Start->esegui e scrivete `cmd.exe`, per Windows Vista/7 cliccate su Start e scrivete direttamente `cmd.exe`).

Posizionatevi tramite la console nella directory **VLCSHARESDEV\_BASEDIR**`\scripts\`.
Gli utenti Windows possono farlo scrivendo (chiaramente adattate il percorso in base al vostro caso)

```
cd C:\Program Files\VLCShares\www\vlc-shares-dev\scripts\
```

Gli utenti Ubuntu possono farlo scrivendo

```
cd /var/www/vlc-shares-dev/scripts/
```

A questo punto siamo pronti a cominciare.

# Realizzazione #

## Fase 1: scegliamo il nostro obbiettivo ##

Come già detto nelle premesse, il plugin che andremo a realizzare è di tipo DataProvider. Questo significa che abbiamo bisogno di un sito target nel quale il nostro plugin dovrà andare a cercare i video. Ho deciso, per questioni di semplicità di realizzazione, di utilizzare il sito `film-stream.tv` in questo esempio.

La struttura del sito è abbastanza semplice, questo schema dovrebbe chiarirla:

```
            HOME ---- Ultimi aggiornamenti
             |           |- serie
             |           |- film
             |           |- anime
             |
             |---- FILM ---- A ----- Titolo A1 ---- Megavideo
             |            |- B    |- Titolo A2   |- Megaupload
             |            |- ...  |- ...         |- ...
             |
             |---- ANIME ---- Titolo A1 ---- Puntata [1x01]
             |             |- Titolo A2   |- Puntata [1x02]
             |             |- Titolo B1   |- Puntata [2x01]
             |             |- ...         |- ...
             |
             |---- FILM (sub-ita) ---- Titolo A1 ---- Megavideo
             |                      |- Titolo B1   |- Megaupload
             |                      |- ...         |- ...
             |
             |---- SERIETV ---- Titolo A1 ---- Puntata [1x01]
                             |- Titolo A2   |- Puntata [1x02]
                             |- Titolo B1   |- Puntata [2x01]
                             |- ...         |- ...
    
```

## Fase 2: immaginiamo il nostro plugin ##

Struttureremo il nostro plugin in modo che sia possibile raggiungere gli indici delle varie tipologie di filmati suddivisi per lettere e che sia anche possibile ottenere gli ultimi aggiornamenti per ogni categoria. Struttureremo i nostri contenuti in questo modo:

```

SELEZIONE TIPO -----> SELEZIONE GRUPPO ---> SEL. TITOLO --> SEL. VIDEO
 |- film               |- Ult. Aggior.       |- Tit. 1       |- Vid 1
 |- anime              |- A                  |- Tit. 2       |- Vid 2
 |- film subita        |- B                  |- ...          |- ...
 |- serie tv           |- ...


```


I plugin per VLCShares sono molto versatili e possono utilizzare molte componenti diverse: oltre al plugin in se è possibile che in ogni pacchetto di installazione contenga al suo interno molti altri componenti come controller, traduzioni, viste, helper, modelli, tabelle... (per una lista completa è bene dare uno sguardo [all'architettura di sistema](PluginsAPI#System_Architecture.md)). In questo caso specifico il nostro plugin per funzionare ha bisogno soltanto della classe principale e dei file di traduzione. Ogni altro componente è superfluo. Aggiungeremo anche una immagine che rappresenterà il logo del plugin che verrà visualizzata nell'indice delle collezioni (vedremo dopo come).

## Fase 3: prepariamo la struttura del nostro plugin ##

Se avete seguito la fase [Prefazione: prepariamo il NOSTRO ambiente di sviluppo](HowToNewPlugin_0_5_4__IT#Prefazione:_prepariamo_il_NOSTRO_ambiente_di_sviluppo.md) dovreste avere una console aperta nella directory **VLCSHARESDEV\_BASEDIR**`/scripts/`.

Utilizzando quella console eseguite il comando:

per WINDOWS
```
php.bat create-plugin.php -k filmstream -n FilmStream -e plugins,languages,images
```

per UBUNTU
```
php create-plugin.php -k filmstream -n FilmStream -e plugins,languages,images
```

L'esecuzione dello script dovrebbe visualizzare come risultato:
```
[EEE] Key already used
```

Non c'è niente di cui preoccuparsi: semplicemente vi viene notificato che un plugin che utilizza la stessa chiave è gia presente e che quindi non è possibile utilizzarla. Questo è abbastanza normale visto che il plugin di questo tutorial è incluso all'interno della versione di sviluppo di vlc-shares dalla versione 0.5.4 in poi. Per poter proseguire, dobbiamo quindi rinominare la cartella **VLCSHARESDEV\_BASEDIR**`/extra/plugins/filmstream/` in **VLCSHARESDEV\_BASEDIR**`/extra/plugins/filmstream-vanilla/`. In questo modo conserverete i file conclusivi che potrete usare come riferimento.

Eseguite nuovamente il comando indicato prima. Questa volta il risultato sarà:
```
All done. Bye
```

Lo script `create-plugin.php` si prenderà carico di creare la struttura delle directory e la lista dei file necessari per il corretto funzionamento del vostro plugin in base ai parametri che gli avrete indicato.
Lo script accetta questi parametri:

```
Usage: create-plugin.php [ options ]
  --key|-k [ <string> ]      Plugin key
  --name|-n [ <string> ]     Plugin name
  --all|-a                   Create all elements
  --ignorekey|-i             Ignore error if the key already exists
  --elements|-e [ <string> ] Create a list of elements (divided by comma). Supported: plugins, helpers, controllers, models, data, views, forms, layouts, languages, images, css, js
--help|-h                  Help -- usage message
```

Nel nostro caso specifico, abbiamo indicato allo script di creare la struttura e i file per un plugin che abbia key `filmstream`, nome `FilmStream`, specificando come elementi richiesti `plugins` (crea il file della classe principale del plugin), `languages` (crea i file delle traduzioni), `images` (crea la directory per l'immagine del logo).

Lo script creerà quanto richiesto all'interno della directory **VLCSHARESDEV\_BASEDIR**`/extra/plugins/filestream/`.

Questo è quello che verrà creato:

```
 extra/plugins/filmstream/
   |-- dev_bootstrap.php
   |-- dev_cleanup.php
   |-- install.sql
   |-- uninstall.sql
   |-- README.txt
   |-- manifest.xml
   |-- languages/
   |     |- X_VlcShares_Plugins_FilmStream.en_GB.ini
   |     |- X_VlcShares_Plugins_FilmStream.it_IT.ini
   |
   |-- public/
   |     |- images/
   |          |- filmstream/
   |
   |-- library/
         |- X/
              |- VlcShares/
                   |- Plugins/
                        |- FilmStream.php
```

**ATTENZIONE**: se avete deciso di ignorare la fase di preparazione dell'ambiente di sviluppo è necessario che creiate manualmente questa struttura di file e directory per poter continuare a seguire il tutorial.

Alcuni degli elementi creati dovrebbero essere già noti, così come la struttura delle directory. Nel caso non lo fosse, mi rimando alla lettura della pagina [sulla creazione dei plugin per la versione 0.5.1](HowToNewPlugin#Version_0.5.1.md) in quanto ancora attuale.

Altri elementi sono stati introdotti dalla versione 0.5.3. I file `dev_bootstrap.php` e `dev_cleanup.php` sono due file utilizzati rispettivamente per l'inizializzazione e la pulizia delle risorse del plugin quando eseguito dall'interno della directory `extra/`.

Normalmente i plugin, per essere inizializzati, necessitano di essere elencati insieme ad alcuni metadati (come la versione e le path dei file), all'interno del database di vlc-shares. Inoltre è necessario che i file siano posizionati in determinati punti del sistema per poter essere utilizzati. Normalmente di questo si occupa il sistema durante l'installazione del plugin utilizzando le informazioni contenute nel file `manifest.xml`. Il problema di questo sistema è che risulta un po' scomodo lavorare sui file di un plugin e poi pacchettizzarlo se è necessario distribuire i file qui e li e dover rimuovere e reinstallare il plugin ogni volta che è necessario apportare delle modifiche e testarle.
Utilizzando il file `dev_bootstrap.php` (dopo averlo modificato a dovere), è possibile indicare al sistema tutte le informazioni necessarie per inizializzare correttamente il plugin.

Come prima fase quindi provvediamo a configurare correttamente il file `dev_bootstrap.php`

Nel nostro caso sarà necessario solo specificare pochi parametri. Modifichiamo l'elenco delle directory e file di cui creare i link

```
/**
 * Insert the needed links
 * Those files or directory will be linked (in linux)
 * or copied (in windows) everytime the application will be executed
 * 
 * Use
 *  APPLICATION_PATH = /vlc-shares/application
 * or
 *  $basePath = the directory where this file is placed
 *  
 * as basepath
 * 
 * Entry format is:
 * 	Real entry path => linked/copied path
 * 
 * Usually language files or image/css/js folders must be setted here
 */
$neededLinks = array(
	//$basePath.'/public/images/myfolder/' => APPLICATION_PATH.'/../public/images/myfolder', // <--- THIS IS AN EXAMPLE FOR FOLDERS
	//$basePath.'/languages/myfile.txt' => APPLICATION_PATH.'/../languages/myfile.txt', // <--- THIS IS AN EXAMPLE FOR FILES
);
```

aggiungendo il percorso dei nostri file di lingua e la nostra directory del logo

```
$neededLinks = array(
	$basePath.'/public/images/filmstream/' => APPLICATION_PATH.'/../public/images/filmstream', // crea un link della cartella delle immagini
	$basePath.'/languages/X_VlcShares_Plugins_FilmStream.en_GB.ini' => APPLICATION_PATH.'/../languages/X_VlcShares_Plugins_FilmStream.en_GB.ini', // link al file di traduzione
	$basePath.'/languages/X_VlcShares_Plugins_FilmStream.it_IT.ini' => APPLICATION_PATH.'/../languages/X_VlcShares_Plugins_FilmStream.it_IT.ini', // link al file di traduzione
);
```

Aggiungiamo poi il percorso al file del plugin all'interno dell'array dei file dei plugin da includere

```
/**
 * Plugin class file to include
 * Use
 *  $basePath = the directory where this file is placed
 * as basepath
 */
$pluginsIncludes = array(
	$basePath.'/library/X/VlcShares/Plugins/FilmStream.php',
);
```

L'ultima modifica da apportare è quella relativa all'inserimento del nome della classe e della chiave del plugin:

```
/**
 * Insert here the pluginKey
 */
$pluginInstance_pluginKey = 'filmstream';

/**
 * Insert here the plugin class
 */
$pluginInstance_pluginClass = 'X_VlcShares_Plugins_FilmStream';
```

Una volta eseguito terminato, salviamo il file.

Passiamo quindi ad impostare il file `dev_cleanup.php`. Questo file verrà per ripulire vlc-shares da tutti i file e i riferimenti inseriti dal file `dev_bootstap.php`. La modifica di questo file è abbastanza banale: basta infatti copiare il contenuto dell'array $neededLinks dal file `dev_bootstrap.php`.

```
/**
 * Copy here the same value inside the $neededLinks
 * of dev_bootstrap.php file
 */
$neededLinks = array(
	$basePath.'/public/images/filmstream/' => APPLICATION_PATH.'/../public/images/filmstream', // crea un link della cartella delle immagini
	$basePath.'/languages/X_VlcShares_Plugins_FilmStream.en_GB.ini' => APPLICATION_PATH.'/../languages/X_VlcShares_Plugins_FilmStream.en_GB.ini', // link al file di traduzione
	$basePath.'/languages/X_VlcShares_Plugins_FilmStream.it_IT.ini' => APPLICATION_PATH.'/../languages/X_VlcShares_Plugins_FilmStream.it_IT.ini', // link al file di traduzione
);
```

Per la compilazione del file `manifest.xml` e le questioni relative a `install.sql` e `uninstall.sql` vi rimando alla guida relativa alla creazione dei plugin per la versione 0.5.1. Potete comunque trovare i file già compilati all'interno della directory `filmstream-vanilla` che avevamo rinominato in precedenza.

## Fase 4: prepariamo la classe del Plugin ##

Finalmente è arrivato il momento di cominciare a lavorare sul plugin vero e proprio. Come già descritto nella guida relativa alla versione 0.5.1, il cuore del nostro plugin è la classe `X_VlcShares_Plugins_FilmStream`. Il file che conterrà questa classe è gia stato creato durante la preparazione in **VLCSHARESDEV\_BASEDIR**`/extra/plugins/filmstream/library/X/VlcShares/Plugins/FilmStream.php`. Non ci rimane che aprire il file e cominciare a scrivere codice.

Come avrete notato il file esiste, ma è vuoto. Tocca a noi cominciare a riempirlo.
Partiamo dai concetti di base: ogni plugin per vlc-shares deve estendere la classe `X_VlcShares_Plugins_Abstract`.

Cominciamo quindi definendo la nostra classe:

```

<?php 

class X_VlcShares_Plugins_FilmStream extends X_VlcShares_Plugins_Abstract implements X_VlcShares_Plugins_ResolverInterface {

	const VERSION = '0.1';
	const VERSION_CLEAN = '0.1';
	
	function __construct() {
		
	}

	/**
	 * @see X_VlcShares_Plugins_ResolverInterface::getLocation()
	 */
	function resolveLocation($location = null) {
		return false;
	}
	/**
	 * @see X_VlcShares_Plugins_ResolverInterface::getParentLocation()
	 */
	function getParentLocation($location = null) {
		return false;
	}

}

```

Come avrete notato, la classe implementa anche un'interfaccia, `X_VlcShares_Plugins_ResolverInterface` appunto. Questa interfaccia definisce due funzioni, nello specifico:

```
<?php

/**
 * Plugins that give browse provider services should
 * implement this interface.
 * Other plugin can use this interface for get
 * real location of a resource
 * @author ximarx
 *
 */
interface X_VlcShares_Plugins_ResolverInterface {
	
	/**
	 * Get the real location of a resource from
	 * $location param
	 * @param string $location
	 * @return string resource real location
	 */
	function resolveLocation($location = null);
	/**
	 * Get the parent location string of the current location
	 * value
	 * 
	 * @param $location
	 * @return string resource parent. NULL if there is no parent, false if location is invalid
	 */
	function getParentLocation($location = null);
	
}

```

Per poter spiegare il senso di questa interfaccia è necessario spiegare per prima cosa il modo in cui vlc-shares gestisce la navigazione. Durante la navigazione dei contenuti delle collezioni, il plugin che sta gestendo la navigazione (da qui in avanti chiamato provider) valorizza un parametro (la $location) che definisce lo stato della navigazione. Il plugin utilizza la $location per definire di fatto un percorso che identifichi la posizione nella collezione. Il modo con il quale la $location viene definita è responsabilità dei provider, che possono di fatto definire un formato autonomamente. L'unica cosa richiesta ai provider è che dopo essere stato formattato, la $location sia codificata tramite la funzione `X_Env::encode(<string>)`. La decodifica viene gestita autonomamente dal sistema.

In questo modo però un plugin che non sia il provider non è in grado di determinare lo stato corrente della navigazione. Per ovviare al problema, un provider che vuole permettere a terzi di ottenere informazioni sullo stato può, implementando `X_VlcShares_Plugins_ResolverInterface` offrire un interfaccia con la quale permettere di decodificare la $location in una normale URI fruibile anche al di fuori del provider.

La funzione `getParentLocation` invece permette di risalire allo stato precedente della navigazione partendo dallo stato attuale.

Sebbene non sia obbligatorio implementare l'interfaccia, lo consiglio caldamente perchè sto valutando l'eventualità di renderlo un requisito per le prossime versioni di vlc-shares.


## Fase 5: aggiungiamo il plugin all'indice delle collezioni ##

Se avete gia provato vlc-shares, saprete che l'indice delle collezioni è quello mostrato comincia a esplorare i contenuti di vlc-shares. L'indice contiene un riferimento ad ogni provider registrato che è in grado di permettere l'esplorazione di una risorsa.

L'inserimento di un elemento all'interno dell'indice avviene tramite i metodi `preGetCollectionsItems`, `getCollectionsItems` e `postGetCollectionsItems`. I provider segnalano al plugin broker che sono interessati ad inserire un elemento all'interno dell'indice, indicando una priorità per uno di quei 3 metodi (se vuoi maggiori informazioni sul sistema di priorità, puoi trovarle [qui](PluginsAPI#Priority_System.md)). Nel nostro caso, è sufficiente modificare il costruttore della classe per settare la priorità del metodo `getCollectionsItems` che andremo subito a implementare.

```
	function __construct() {
		$this->setPriority('getCollectionsItems');
	}
```

Il metodo `getCollectionsItems` prevede che un plugin che voglia inserire uno o più elementi all'interno dell'indice, deve restituire come valore di ritorno una instanza della classe `X_Page_ItemList_PItem`. Questa classe è la rappresentazione di una lista di oggetti `X_Page_Item_PItem`. Ogni singolo oggetto della classe `X_Page_Item_PItem` rappresenta un elemento della playlist. E' possibile specificare molte informazioni tramite questo oggetto (ad esempio un icona, un'immagine di anteprima, una descrizione...), ne vedremo alcuni più in dettaglio. Il sistema provvederà a convertire le informazioni incapsulate in questo oggetto in una rappresentazione consona al tipo di dispositivo che richiederà l'esplorazione.

Nel nostro caso specifico, aggiungeremo un solo elemento all'indice che avrà il compito di consentire la navigazione all'interno delle risorse di FilmStream.

```
	/**
	 * Add the FilmStream link inside collection index
	 * @param Zend_Controller_Action $controller
	 */
	public function getCollectionsItems(Zend_Controller_Action $controller) {
		
		X_Debug::i("Plugin triggered");
		
		$link = new X_Page_Item_PItem($this->getId(), X_Env::_('p_filmstream_collectionindex'));
		$link->setIcon('/images/filmstream/logo.png')
			->setDescription(X_Env::_('p_filmstream_collectionindex_desc'))
			->setType(X_Page_Item_PItem::TYPE_CONTAINER)
			->setLink(
				array(
					'controller' => 'browse',
					'action' => 'share',
					'p' => $this->getId(),
				), 'default', true
			);
		return new X_Page_ItemList_PItem(array($link));
	}

```

Tralasciando proprietà più ovvie come `setLabel` o `setDescription`, passiamo subito a spiegare il senso di metodi più particolari.

Il metodo `setType` consente di definire il tipo di elemento che verrà rappresentato dall'oggetto `X_Page_Item_PItem`. In questo caso, poichè il nostro elemento aggiunto è un contenitore di altre risorse, specifichiamo come tipo `X_Page_Item_PItem::TYPE_CONTAINER`. Altri valori possibili sono
`X_Page_Item_PItem::TYPE_PLAYABLE` (rappresenta un elemento che può essere riprodotto), `X_Page_Item_PItem::TYPE_ELEMENT` (un elemento generico, può rappresentare qualsiasi cosa), `X_Page_Item_PItem::TYPE_REQUEST` (rappresenta un elemento che richiede una qualche interazione con l'utente per l'inserimento di valori).

Il metodo `setLink` permette di specificare la risorsa che verrà esplorata selezionando questo elemento. Specificando un array associativo, potremo specificare un insieme di parametri che verranno passati alla richiesta successiva qualora l'elemento venisse selezionato. In questo caso stiamo specificando che, nel caso di selezione, il controllo del sistema deve essere affidato al controller `BrowseController` e all'azione `shareAction`. Tutti gli altri elementi specificati nell'array verranno trattati come parametri (chiave => valore) passati direttamente all'azione. Si può passare qualsiasi valore arbitrario senza alcun genere di controllo, fatta eccezione per alcuni parametri riservati a scopi specifici.

Questa è una lista dei parametri riservati:

| **chiave** | **nome discorsivo** | **descrizione** | **note sui valori** |
|:-----------|:--------------------|:----------------|:--------------------|
| p | provider | rappresenta il plugin che ha il compito di gestire la navigazione nel sistema e che ha il compito di gestire la l (location) | il suo valore è sempre uguale alla chiave di un plugin |
| l | location | rappresenta lo stato attuale di navigazione del sistema. Ogni provider può definirne arbitrariamente il formato e deve provvedere a codificarne il valore tramite la funzione X\_Env::encode | Codificato tramite X\_Env::encode |
| pid | pluginId | Indica la chiave di un plugin che deve gestire una funzione secondaria | è sempre uguale alla chiave di un plugin |
| a | activity | rappresenta una attività da eseguire |  |
| param | parametro | rappresenta un valore arbitrario associato ad una activity | solitamente richiesto all'utente tramite interazione |

Specificando quindi il valore di provider pari a `$this->getId()` stiamo indicando al sistema che il provider che gestirà la navigazione nel caso di selezione di questa risorsa sarà proprio questo plugin.

I valori di `action` e `controller` indicano il passaggio del sistema nella fase di navigazione. Un grafico degli stati di navigazione è disponibile [nell'architettura di sistema](PluginsAPI#System_Architecture.md).


## Fase 6: testiamo quanto fatto finora ##

Lo so, siamo ancora all'inizio. Però è il momento di cominciare a guardare qualche risultato. La fase preparatoria del `dev_cleanup.php` ci permette di poter testare in maniera agevole il plugin senza doverlo installare. Per farlo basta recarsi nella pagina delle configurazioni di vlc-shares (`http://localhost/vlc-shares-dev/public/configs`), mostrare le configurazioni avanzate e specificare all'interno della lista di plugin opzionali da caricare, la chiave del nostro plugin, in questo caso `filmstream`. Vedremo più avanti che è possibile specificare un elenco di plugin da caricare semplicemente separando ogni singola chiave con una virgola. Salviamo le modifiche (visto che ci siete, attivare il debug log e impostarne il livello su TUTTO non sarebbe una cattiva idea) e andiamo all'indice delle collezioni (il pulsante Browse nella barra superiore di vlc-shares).

Eccolo li, il nostro nuovo fiammante pulsante per FilmStream che compare.

![https://lh5.googleusercontent.com/_U6HIkh_ODAo/TZOx70OiwiI/AAAAAAAAAGg/4kvP0N0-4EU/s800/plugin_0.5.4_tutorial_1.png](https://lh5.googleusercontent.com/_U6HIkh_ODAo/TZOx70OiwiI/AAAAAAAAAGg/4kvP0N0-4EU/s800/plugin_0.5.4_tutorial_1.png)


## Fase 7: prepariamo l'indice ##

Come avrete sicuramente notato, cliccando sul pulsante appena creato non verrà visualizzato assolutamente nulla. E' ora di cominciare a riempire qualcosa.
La navigazione all'interno di un provider viene gestita dalla pagina `browse/shares` (`browseController::shareAction()`). Il comportamento della pagina e le interazioni con i plugin sono molto semplici:

  * in controller invoca in successione i trigger `preGetShareItems`, `getShareItems` e `postGetShareItems`. Il loro funzionamento è analogo a `getCollectionsItems`: generano oggetti di tipo `X_Page_Item_PItem` raccolti all'interno di liste di tipo `X_Page_ItemList_PItem`. Le tre liste generate dai tre trigger vengono fuse insieme per generarne una unica che conservi l'ordinamento. In questa fase verranno generati i contenuti visualizzati.
  * una volta che tutti i contenuti sono stati generati, il controller invoca per ogni contenuto il trigger `filterShareItems`. Questo trigger permette a tutti i plugin che si sono registrati di esprimere un valore booleano che ne determini un'eventuale rimozione dalla lista. Se anche solo uno dei plugin restituirà `false`, l'elemento verrà rimosso dalla lista.
  * filtrata la lista, l'ultimo passaggi di preparazione riguarda l'ordinamento dei contenuti. Il controller chiama il trigger `orderShareItems` dando in input un array di elementi `X_Page_Item_PItem` per permetterne l'ordinamento in maniera arbitraria. Nelle installazioni standard di vlc-shares è attivo un plugin di default (`X_VlcShares_Plugins_SortItems`) che si occupa proprio di ordinare gli elementi per tipo e in ordine alfabetico. Vedremo più avanti come disattivarlo nel caso in cui non volessimo questo tipo di servizio.
  * ultimata la preparazione, il controller delega le ultime operazioni ai plugin di tipo Renderer tramite `gen_afterPageBuild`: questo tipo di plugin hanno lo scopo di trasformare gli elementi generati nei passaggi precedenti, in una forma utilizzabile dal device che sta generando la richiesta della pagina (ad esempio: se si sta navigando tramite Wii sarà compito del WiimcPlxRenderer trasformarli in una playlist PLX, se tramite un browser questo sarà onere del MobileRenderer). In ogni caso non discuterò ulteriormente in questa guida le implicazioni di questo trigger visto che è un argomento che merità una trattazione separata.

Ritorniamo al nostro plugin: come avrete sicuramente inteso, l'intera navigazione all'interno dei contenuti avviene sempre in questa pagina. Per determinare la nostra posizione all'interno delle risorse e decidere quali visualizzare utilizzeremo il parametro `location`. Come già detto, il formato di questo parametro è arbitrario. Dobbiamo quindi decidere come gestirlo in base alle nostre esigente.
Durante la _Fase 2_ abbiamo pianificato come dovranno essere organizzate le nostre risorse. Partendo da li possiamo decidere il modo più idoneo per il formato della nostra `location`.

Utilizzeremo un separatore `/` per separare i parametri contenuti nella location. Non dobbiamo preoccuparci che questo possa interferire con altri parametri di vlc-shares in quanto codificando la `location` tramite `X_Env::encode()` scongiureremo proprio questo genere di eventualità.

In definitiva la nostra `location` completa verrà rappresentata in questo modo:

```
TIPO_RISORSA/TIPO_GRUPPO/N_PAGINA/ID_TITOLO/ID_FILMATO
```

Per rendere chiaro questo passaggio vi faccio qualche esempio:

```
film/I/2/inception/mv:12345678
```
Indica che stiamo visualizzando la risorsa nella categoria "film", gruppo "i", pagina "2", id elemento "inception", id filmato "mv:12345678". Questa è la location che rappresenta una URL ad un filmato. Tramite `getLocation` potremo infatti ottenere URL diretto ad un video.

```
film/I/inception/
```
Indica che stiamo visualizzando la risorsa nella categoria "film", gruppo "i", pagina "2", id elemento "inception". Questa `location` rappresenta la pagina indice dei filmati per l'elemento "inception". Il provider genererà quindi l'elenco dei filmato relativi ad "inception"

Utilizzando questa notazione possiamo sempre ottenere facilmente la pagina padre (basterà eliminare ultimo parametro della location) e capire al volo quali risorse generare (quelle in grado di impostare il parametro mancante).

Traduciamo questi concetti in codice aggiungendo due nuovi metodi alla nostra classe `X_VlcShares_Plugins_FilmStream`.

```
	/**
	 * Fetch resources from filmstream site
	 * @param string $provider the plugin key of the one who should handle the request
	 * @param string $location the current $location
	 * @param Zend_Controller_Action $controller the controller who handle the request
	 * @return X_Page_ItemList_PItem
	 */
	public function getShareItems($provider, $location, Zend_Controller_Action $controller) {
		// this plugin fetch resources only if it's the provider
		if ( $provider != $this->getId() ) return;
		// add an info inside the debug log so we can trace this call 
		X_Debug::i('Plugin triggered');
		// disable automatic sorting, items will be already sorted in the target site
		X_VlcShares_Plugins::broker()->unregisterPluginClass('X_VlcShares_Plugins_SortItems');
		// let's create the itemlist
		$items = new X_Page_ItemList_PItem();
		// show the requested location in the debug log
		// $location has been already decoded
		X_Debug::i("Requested location: $location");
		
		// location format:
		// resourceType/resourceGroup/page/resourceId/videoId
		
		$split = $location != '' ? @explode('/', $location, 5) : array();
		@list($resourceType, $resourceGroup, $page, $resourceId, $videoId) = $split;
		
		X_Debug::i("Exploded location: ".var_export($split, true));

		// Choose what to do based on the number of params
		// setted inside $location
		switch ( count($split) ) {
			case 5:
				// we shouldn't be here!
				// if we have 5 pieces (so even $videoId is setted)
				// we should be inside the browse/mode page
				// because the location is about a video URL
			case 4:
				// delegate to fetchVideos
				$this->_fetchVideos($items, $resourceType, $resourceGroup, $page, $resourceId);
				break;
			case 2:
				$page = 1;
			case 3:
				// delegate to fetchResources
				$this->_fetchResources($items, $resourceType, $resourceGroup, $page);
				break;
			case 1:
				// fetchGroups doesn't require any kind of network traffic
				// so it's useless to cache the results
				$this->disableCache();
				// delegate to fetchGroups
				$this->_fetchGroups($items, $resourceType);
				break;
			
			case 0:
			default: 
				// fetchTypes doesn't require any kind of network traffic
				// so it's useless to cache the results
				$this->disableCache();
				// delegate to fetchTypes
				$this->_fetchTypes($items);
			
		}
				
		
		return $items;
		
	}
	
	/**
	 * Disable cache plugin is registered and enabled
	 */
	private function disableCache() {
		
		if ( X_VlcShares_Plugins::broker()->isRegistered('cache') ) {
			$cache = X_VlcShares_Plugins::broker()->getPlugins('cache');
			if ( method_exists($cache, 'setDoNotCache') ) {
				$cache->setDoNotCache();
			}
		}
		
	}
	
```

Il primo metodo `getShareItems` è il nostro trigger che gestirà la navigazione. Ho deciso di strutturare il metodo in modo che il suo unico compito sia quello di analizzare il formato della `$location` e di delegare la generazione degli elementi a dei metodi privati (che aggiungeremo in seguito) in base al tipo di parametri trovati. Nei commenti in linea potete trovare informazioni specifiche su quello che succede.

Mi soffermo semplicemente su l'analisi di quello che succede alla `$location`:

```
		// location format:
		// resourceType/resourceGroup/page/resourceId/videoId
		
		$split = $location != '' ? @explode('/', $location, 5) : array();
		@list($resourceType, $resourceGroup, $page, $resourceId, $videoId) = $split;
```

inseriamo in `$split` un array dei parametri memorizzati in `$location` (dopo averli divisi utilizzando '/' come separatore). In seguito assegnamo in base alla posizione, i valori contenuti all'interno dell'array esploso a delle variabili.

Fatto questo, andremo a valutare cosa fare in base al numero di parametri individuati:

  * se non sarà presente alcun parametro (`$location` vuota), sigificherà che dovremo visualizzare la pagina di selezione del TIPO\_RISORSA
  * se sarà presente solo il TIPO\_RISORSA, visualizzeremo quella di selezione di TIPO\_GROUPPO
  * ...cosi via...

Dopo aver implementato il nostro trigger, dobbiamo segnalarlo al broker aggiungendo una priorità. Così come abbiamo fatto per `getCollectionsItems`, ripetiamo la procedura anche per `getShareItems`. Modifichiamo il costruttore:

```
	function __construct() {
		$this->setPriority('getCollectionsItems');
		$this->setPriority('getShareItems');
	}

```



A questo punto cerchiamo di risolvere il primo scenario: `ho appena cliccato nell'indice delle collezioni su FilmStream e quindi non ho ancora nessun parametro in $location`.

All'interno del nostro `switch`, questo scenario è rappresentato dal `case`:

```

			case 0:
			default: 
				// fetchTypes doesn't require any kind of network traffic
				// so it's useless to cache the results
				$this->disableCache();
				// delegate to fetchTypes
				$this->_fetchTypes($items);

```

Andiamo a implementare quindi `_fetchTypes($items)` come metodo privato.

```

	const TYPE_MOVIES = 'movies';
	const TYPE_TVSHOWS = 'tv';
	const TYPE_ANIME = 'anime';
	const TYPE_SUBBED = 'subbed';
	
	/**
	 * Fill a list of types of resoures
	 * @param X_Page_ItemList_PItem $items an empty list
	 * @return X_Page_ItemList_PItem the list filled
	 */
	private function _fetchTypes(X_Page_ItemList_PItem $items) {
		
		$types = array(
			self::TYPE_MOVIES => X_Env::_('p_filmstream_type_movies'),
			self::TYPE_TVSHOWS => X_Env::_('p_filmstream_type_tvshows'),
			self::TYPE_ANIME => X_Env::_('p_filmstream_type_anime'),
			self::TYPE_SUBBED => X_Env::_('p_filmstream_type_subbed'),
		);
		
		foreach ( $types as $typeLocParam => $typeLabel ) {
			$item = new X_Page_Item_PItem($this->getId()."-type-$typeLocParam", $typeLabel);
			$item->setIcon('/images/icons/folder_32.png')
				->setType(X_Page_Item_PItem::TYPE_CONTAINER)
				->setCustom(__CLASS__.':location', "$typeLocParam")
				->setDescription(APPLICATION_ENV == 'development' ? "$typeLocParam" : null)
				->setLink(array(
					'l'	=>	X_Env::encode("$typeLocParam")
				), 'default', false);
				
			$items->append($item);
		}
	}
```

Quello che fa la funzione è piuttosto semplice: crea per ogni elemento inserito all'interno dell'array `$types` un nuovo elemento, usando la chiave `$typeLocParam` come parametro della `location` e il valore `$typeLabel` come etichetta per l'elemento.

**Importante**: osservate come il parametro `l` (`location`) venga codificato tramite `X_Env::encode`.

Possiamo adesso testare quanto aggiunto cliccando su FilmStream all'interno della lista delle collezioni.


![https://lh5.googleusercontent.com/_U6HIkh_ODAo/TZRGTBtUqXI/AAAAAAAAAGw/8cRDq48YYtI/s800/plugin_0.5.4_tutorial_2.png](https://lh5.googleusercontent.com/_U6HIkh_ODAo/TZRGTBtUqXI/AAAAAAAAAGw/8cRDq48YYtI/s800/plugin_0.5.4_tutorial_2.png)


## Fase 8: preparamo la selezione dei gruppi ##

Come visto nella fase preparatoria e in quella precendente a questa, dopo l'indice principale è la volta di gestire lo scenario in cui l'utente ha selezionato uno dei 4 tipi visualizzati.
Lo scenario è abbastanza semplice: quello che dobbiamo fare è visualizzare una lista di gruppi di risorse (faremo una divisione per lettere e aggiungeremo anche il gruppo "Novità" in testa).

Questo caso corrisponde a quello in cui nella `$location` venga specificato un solo parametro:

```

			case 1:
				// fetchGroups doesn't require any kind of network traffic
				// so it's useless to cache the results
				$this->disableCache();
				// delegate to fetchGroups
				$this->_fetchGroups($items, $resourceType);
				break;


```


Andiamo quindi a implementare il metodo privato `_fetchGroups($items, $resourceType)` passando come parametri l'elenco da riemprire e (tramite `$resourceType`) il tipo attualmente selezionato.

```

	/**
	 * Fill a list of groups of resoures by type
	 * @param X_Page_ItemList_PItem $items an empty list
	 * @param string $resourceType the resource type selected
	 * @return X_Page_ItemList_PItem the list filled
	 */
	private function _fetchGroups(X_Page_ItemList_PItem $items, $resourceType) {

		if ( $resourceType == self::TYPE_MOVIES ) {
			$groups = 'new,0-9,a-b,c-d,e-f,g-h-j-k,i-l,m-n,o-p,q-r-s,t-u-v,w-x-y-z';
		} else {
			$groups = 'new,all';
		}
		$groups = explode(',', $groups);
		
		foreach ( $groups as $group ) {
			$item = new X_Page_Item_PItem($this->getId()."-$resourceType-$group", ($group == 'new' || $group == 'all' ? X_Env::_("p_filmstream_group_$group") : strtoupper($group)));
			$item->setIcon('/images/icons/folder_32.png')
				->setType(X_Page_Item_PItem::TYPE_CONTAINER)
				->setCustom(__CLASS__.':location', "$resourceType/$group")
				->setDescription(APPLICATION_ENV == 'development' ? "$resourceType/$group" : null)
				->setLink(array(
					'l'	=>	X_Env::encode("$resourceType/$group")
				), 'default', false);
				
			$items->append($item);
		}
	}

```

Questo metodo funziona più o meno come il `_fetchTypes` con una piccola differenza: il tipo di gruppi da visualizzare viene deciso in base al tipo di `$resourceType` che l'utente ha selezionato.

Sul sito FilmStream nello specifico i film vengono divisi in gruppi alfabetici di più lettere, mentre gli altri tipi di risorse sono accorpate tutte insieme nella stessa pagina. Questo richiede di effettuare un distinguo, quello che fa il primo `if`.

Altra nota importante riguarda l'uso della `$location`. Come vediamo, il parametro `l` viene compilato inserendo la vecchia selezione e la nuova in successione (`$resourceType/$group`) per ogni singolo gruppo. Questo ci permetterà di passare al caso successivo (quello a 2 parametri) cliccando uno degli elementi generati qui.

Possiamo provare le modifiche selezionando uno dei tipi.

![https://lh6.googleusercontent.com/_U6HIkh_ODAo/TZRP6sDXPKI/AAAAAAAAAG0/FfxxkfxQlTc/s800/plugin_0.5.4_tutorial_3.png](https://lh6.googleusercontent.com/_U6HIkh_ODAo/TZRP6sDXPKI/AAAAAAAAAG0/FfxxkfxQlTc/s800/plugin_0.5.4_tutorial_3.png)


## Fase 9: leggiamo i titoli dal sito ##

Arrivati a questo punto abbiamo a disposizione (in quanto selezionato dall'utente e quindi presente nella `$location`) il tipo e il gruppo di risorse al quale siamo interessati. Non ci rimane che leggere dal sito i titoli delle risorse
Questa fase riguarda il `case` a 2 e 3 parametri. Li gestiremo insieme in quanto sul sito non è presente una divisione dei contenuti in più pagine per categoria, quindi il caso a 2 parametri (cioè quello in cui il numero di pagina non è selezionato) verrà considerato come se il numero di pagina indicato sia 1. Toccherà poi a noi suddividere i risultati in pagine e gestire la paginazione

```

			case 2:
				$page = 1;
			case 3:
				// delegate to fetchResources
				$this->_fetchResources($items, $resourceType, $resourceGroup, $page);
				break;

```


E' arrivato il momento di implementare la estrapolazione dei contenuti veri e propri dal sito. Purtroppo il metodo `_fetchResources` è abbastanza complesso, quindi prima di analizzarlo nel dettaglio è necessario fare qualche considerazione.

Quando decidiamo di realizzare questo genere di plugin dobbiamo tenere a mente che la parte più complicata sarà quella relativa alla ricerca delle informazioni importanti all'interno delle pagine. Nel nostro caso, le informazioni importanti, quelle che cerchiamo, sono quelle relative agli indirizzi delle pagine in cui sono contenuti i video e ai titoli degli elementi.

Il caso ideale sarebbe quello in cui le informazioni siano tabulate in maniera coerente e che sia individuabile uno schema comune fra tutte. Questo si permetterebbe di isolare in maniera immediata le informazioni importanti da quelle totalmente inutili. Chiaramente, nel nostro caso ci troviamo nella situazione opposta. Nel sito FilmStream i dati relativi alle pagine sono scritti in maniera abbastanza incoerente fra di loro. Spesso sono presenti formattazioni differenti. Nei casi come questo, il mio consiglio è quello di individuare un modo generale che consenta al maggior numero di elementi di essere individuato correttamente e considerare una stategia di riserva in grado di gestire le anomalie.

Entriamo più nello specifico. A titolo di esempio, analizzerò nel dettaglio solo uno dei casi, gli altri possono essere ricondotti (con le opportune modifiche) a quello. Purtroppo da questo punto in poi la questione diventa abbastanza complicata.

Analizziamo la struttura della pagina indice delle serie tv(l'indirizzo è questo: http://film-stream.tv/serietv/lista-tv/). La struttura della pagina può essere semplificata in questo modo.

```
<html>
...
<body>
...
<div id="maincontent">
...
<p>
 <strong>
   <a href="INDIRIZZO">TITOLO RISORSA</a>
 </strong>
</p>
...
</div>
...
</body>
</html>

```

La maggior parte degli elementi ricercati viene rappresentata all'interno di un tag `<p>` e `<strong>`. Utilizzeremo il valore dell'attributo `href` per ottenere l'indirizzo della pagina dei video e il valore testuale nel tag `<a>` come etichetta per il nostro elemento.

Il modo con il quale isolate gli elementi cercati dalla pagina è una vostra decisione. Non ci sono vincoli. Finora, per tutti i plugin che ho scritto ho utilizzato due tipi di strategie:
  * pattern matching
  * dom traversal e xpath

La prima strategia consiste nel ricercare nel testo delle stringhe che corrispondano a dei "modelli". Vi rimando a Google
o Wikipedia per cercare maggiori informazioni sull'argomento.

La seconda strategia invece consiste nell'usare il modello DOM per navigare all'interno della pagina come se fosse un albero e utilizzare delle query XPath per poter ottenere dei riscontri specifici. Anche in questo caso Wikipedia e Google sono una buona fonte per maggiori informazioni.

In questo caso specifico ho deciso di utilizzare il secondo metodo, anche se più scomodo per un semplice motivo: leggendo il codice sorgente delle pagine si nota subito che realizzare un pattern in grado di riuscire a comprendere un numero soddisfacente di elementi è molto difficile considerando il fatto che molti dei nodi vengono indicati con tag e attributi completamente fuori dallo schema generale.

Utilizzando XPath (anche se il problema relativo ai tag resta), almeno possiamo evitare i problemi relativi agli attributi non coerenti che portavano ai fallimenti nel riconoscimento dei pattern.

Fatte queste premesse, possiamo passare alla parte pratica.


```

	/**
	 * Fill a list of resource by type, group and page
	 * @param X_Page_ItemList_PItem $items an empty list
	 * @param string $resourceType the resource type selected
	 * @param string $resourceGroup the resource group selected
	 * @param int $page number of the page 
	 * @return X_Page_ItemList_PItem the list filled
	 */
	private function _fetchResources(X_Page_ItemList_PItem $items, $resourceType, $resourceGroup, $page = 1) {
		
		X_Debug::i("Fetching resources for $resourceType/$resourceGroup/$page");
		
		// if resourceGroup == new, query and url are specials
		if ( $resourceGroup == 'new' ) {
			switch ( $resourceType ) {
				case self::TYPE_MOVIES:
					$url = self::URL_MOVIES_INDEX_NEW;
					$xpathQuery = '//div[@id="maincontent"]//div[@class="galleryitem"]';
					break;
				case self::TYPE_ANIME:
					$url = self::URL_ANIME_INDEX_NEW;
					$xpathQuery = '//div[@id="sidebar"]//div[@id="text-5"]//tr[11]//td';
					break;
				case self::TYPE_TVSHOWS:
					$url = self::URL_TVSHOWS_INDEX_NEW;
					$xpathQuery = '//div[@id="sidebar"]//div[@id="text-5"]//tr[position()=1 or position()=4 or position()=5]//td//a/parent::node()';
					break;
			}
		} else {
			switch ( $resourceType ) {
				case self::TYPE_MOVIES:
					$url = sprintf(self::URL_MOVIES_INDEX_AZ, $resourceGroup);
					$xpathQuery = '//div[@id="maincontent"]//p/*/a[1][node()][text()]';
					$hasThumbnail = false;
					$hasDescription = false;
					break;
				case self::TYPE_ANIME:
					$url = self::URL_ANIME_INDEX_AZ;
					$xpathQuery = '//div[@id="maincontent"]//p/*/a[1][node()][text()]';
					break;
				case self::TYPE_TVSHOWS:
					$url = self::URL_TVSHOWS_INDEX_AZ;
					$xpathQuery = '//div[@id="maincontent"]//p/*/a[1][node()][text()]';
					break;
			}
		}
		
		// fetch the page from filmstream (url is different for each $resourceType)
		$htmlString = $this->_loadPage($url);
		// load the readed page inside a DOM object, so we can user XPath for traversal
		$dom = new Zend_Dom_Query($htmlString);
		// execute the query
		$result = $dom->queryXpath($xpathQuery);
		
		if ( $result->valid() ) {
			
			X_Debug::i("Resources found: ".$result->count());

			$perPage = $this->config('items.perpage', 50);
			
			// before to slice the results, we must check if a -next-page- is needed
			$nextNeeded = ($result->count() > ($page * $perPage) ? true : false );
			
			$matches = array();
			$i = 1;
			while ( $result->valid() ) {
				if (  $i > ( ($page -1) * $perPage ) &&  $i < ($page * $perPage)  ) {
					$currentRes = $result->current();
					
					if ( $resourceGroup == 'new' ) {
						
						$IdNode = $currentRes->firstChild;
						while ( $IdNode instanceof DOMText && $IdNode != null ) {
							$IdNode = $IdNode->nextSibling;
						}
						// anime, tvshow, subbed are on the side bar, and $currentRes has 1 child only
						if ( $currentRes->childNodes->length == 1 ) {
							$labelNode = $IdNode;
						} else {
							$labelNode = $IdNode->nextSibling;
						}
						if ( trim($labelNode->nodeValue) == '' ) $labelNode = $labelNode->parentNode;
						
						$resourceId = str_replace('/', ':', substr( $IdNode->getAttribute('href'), strlen('http://film-stream.tv/'), -1 ) );
						
						// i've done everthing. If all doesn't work, just skip this entry
						if ( $resourceId == "" ) {
							$i++;
							$result->next();
							continue;
						}
						
						$resourceLabel = trim($labelNode->nodeValue);
						$resourceDescription = null;
						$resourceThumbnail = ($IdNode->firstChild != null ? $IdNode->firstChild->getAttribute('src') : null );
					} else {
						$resourceId = str_replace('/', ':', substr( $currentRes->getAttribute('href'), strlen('http://film-stream.tv/'), -1 ) );
						$resourceLabel = trim($currentRes->nodeValue);
						$resourceDescription = null;
						$resourceThumbnail = null;
					}
					$matches[] = array($resourceId, $resourceLabel, $resourceDescription, $resourceThumbnail);
				}
				$i++;
				$result->next();
			}
			
			if ( $page > 1 ) {
				// we need the "previus-page" link
				$item = new X_Page_Item_PItem($this->getId()."-previouspage", X_Env::_("p_filmstream_page_previous", ($page - 1)));
				$item->setType(X_Page_Item_PItem::TYPE_CONTAINER)
					->setCustom(__CLASS__.':location', "$resourceType/$resourceGroup/".($page - 1))
					->setLink(array(
						'l'	=>	X_Env::encode("$resourceType/$resourceGroup/".($page - 1))
					), 'default', false);
				$items->append($item);
			}
			
			foreach ($matches as $resource) {
				
				@list($resourceId, $resourceLabel, $resourceDescription, $resourceThumbnail) = $resource;
				
				$item = new X_Page_Item_PItem($this->getId()."-$resourceType-$resourceGroup-$page-$resourceId", $resourceLabel);
				$item->setIcon('/images/icons/folder_32.png')
					->setType(X_Page_Item_PItem::TYPE_CONTAINER)
					->setCustom(__CLASS__.':location', "$resourceType/$resourceGroup/$page/$resourceId")
					->setLink(array(
						'l'	=>	X_Env::encode("$resourceType/$resourceGroup/$page/$resourceId")
					), 'default', false);
					
				if ( $resourceDescription != null ) {
					$item->setDescription($resourceDescription);
				} elseif ( APPLICATION_ENV == 'development' ) {
					$item->setDescription("$resourceType/$resourceGroup/$page/$resourceId");
				}
				if ( $resourceThumbnail != null ) {
					$item->setThumbnail($resourceThumbnail);
				}
				
				$items->append($item);
				
			}
			
			if ( $nextNeeded ) {
				// we need the "previus-page" link
				$item = new X_Page_Item_PItem($this->getId()."-nextpage", X_Env::_("p_filmstream_page_next", ($page + 1)));
				$item->setType(X_Page_Item_PItem::TYPE_CONTAINER)
					->setCustom(__CLASS__.':location', "$resourceType/$resourceGroup/".($page + 1))
					->setLink(array(
						'l'	=>	X_Env::encode("$resourceType/$resourceGroup/".($page + 1))
					), 'default', false);
				$items->append($item);
			}
			
		} else {
			X_Debug::e("Query failed {{$xpathQuery}}");
		}
		
	}

```

Analizziamo nello dettaglio: all'inizio del metodo troviamo subito una serie di `if`/`switch` che hanno lo scopo di specificare in base alla richiesta, l'indirizzo della pagina che contiene le risorse è una query XPath abbastanza generica in grado di estrappolarle.

Subito dopo queste informazioni vengono utilizzate per ottenere la pagina dal sito, caricarlo in un oggetto DOM e eseguire su di esso la query.

```
		// fetch the page from filmstream (url is different for each $resourceType)
		$htmlString = $this->_loadPage($url);
		// load the readed page inside a DOM object, so we can user XPath for traversal
		$dom = new Zend_Dom_Query($htmlString);
		// execute the query
		$result = $dom->queryXpath($xpathQuery);
```

Dopo l'esecuzione della query, la nostra variabile `$result` conterrà l'insieme dei nodi che hanno avuto corrispondenza con la nostra query.

A questo punto procediamo con la paginazione degli elementi: visto che il numero di elementi trovato è arbitrario e non deterministico, prima di processare tutti gli elementi per suddividerne le componenti procediamo ad una scrematura di quelli che non fanno parte della sezione di quelli che verranno visualizzati.

```

			$matches = array();
			$i = 1;
			while ( $result->valid() ) {
				if (  $i > ( ($page -1) * $perPage ) &&  $i < ($page * $perPage)  ) {
					$currentRes = $result->current();
					
					if ( $resourceGroup == 'new' ) {
						
						$IdNode = $currentRes->firstChild;
						while ( $IdNode instanceof DOMText && $IdNode != null ) {
							$IdNode = $IdNode->nextSibling;
						}
						// anime, tvshow, subbed are on the side bar, and $currentRes has 1 child only
						if ( $currentRes->childNodes->length == 1 ) {
							$labelNode = $IdNode;
						} else {
							$labelNode = $IdNode->nextSibling;
						}
						if ( trim($labelNode->nodeValue) == '' ) $labelNode = $labelNode->parentNode;
						
						$resourceId = str_replace('/', ':', substr( $IdNode->getAttribute('href'), strlen('http://film-stream.tv/'), -1 ) );
						
						// i've done everthing. If all doesn't work, just skip this entry
						if ( $resourceId == "" ) {
							$i++;
							$result->next();
							continue;
						}
						
						$resourceLabel = trim($labelNode->nodeValue);
						$resourceDescription = null;
						$resourceThumbnail = ($IdNode->firstChild != null ? $IdNode->firstChild->getAttribute('src') : null );
					} else {
						$resourceId = str_replace('/', ':', substr( $currentRes->getAttribute('href'), strlen('http://film-stream.tv/'), -1 ) );
						$resourceLabel = trim($currentRes->nodeValue);
						$resourceDescription = null;
						$resourceThumbnail = null;
					}
					$matches[] = array($resourceId, $resourceLabel, $resourceDescription, $resourceThumbnail);
				}
				$i++;
				$result->next();
			}

```

Per farlo useremo un ciclo while che ignorerà tutti gli elementi fino al primo della pagina che visualizzeremo. Una volta arriveti al punto richiesto, comincierà la scansione.

Purtroppo, quello che posso dirvi su questa parte è piuttosto poco. Semplicemente, la serie di `if` innestati serve per gestire la maggior parte dei casi anomali e per poter gestire le pagine delle novità. La strategia che ho adottato era quella di tentare di individuare un nodo figlio a quello selezionato che potesse contenere con maggiore probabilità le informazioni cercate. Chiaramente il metodo non è infallibile e per questo ho inserito una guard:

```
						if ( $resourceId == "" ) {
							$i++;
							$result->next();
							continue;
						}

```

Il suo scopo è quello di ignorare l'elemento se dopo tutti i tentativi non si è ancora arrivati ad individuare un ID\_RISORSA valido.

Tutti gli elementi validi verranno inseriti sotto forma di array, all'interno dell'array `$matches`.

A questo punto non ci rimane che trasformare quanto trovato in una lista di oggetti `X_Page_Item_PItem`.

```

			
			if ( $page > 1 ) {
				// we need the "previus-page" link
				$item = new X_Page_Item_PItem($this->getId()."-previouspage", X_Env::_("p_filmstream_page_previous", ($page - 1)));
				$item->setType(X_Page_Item_PItem::TYPE_CONTAINER)
					->setCustom(__CLASS__.':location', "$resourceType/$resourceGroup/".($page - 1))
					->setLink(array(
						'l'	=>	X_Env::encode("$resourceType/$resourceGroup/".($page - 1))
					), 'default', false);
				$items->append($item);
			}

			foreach ($matches as $resource) {
				
				@list($resourceId, $resourceLabel, $resourceDescription, $resourceThumbnail) = $resource;
				
				$item = new X_Page_Item_PItem($this->getId()."-$resourceType-$resourceGroup-$page-$resourceId", $resourceLabel);
				$item->setIcon('/images/icons/folder_32.png')
					->setType(X_Page_Item_PItem::TYPE_CONTAINER)
					->setCustom(__CLASS__.':location', "$resourceType/$resourceGroup/$page/$resourceId")
					->setLink(array(
						'l'	=>	X_Env::encode("$resourceType/$resourceGroup/$page/$resourceId")
					), 'default', false);
					
				if ( $resourceDescription != null ) {
					$item->setDescription($resourceDescription);
				} elseif ( APPLICATION_ENV == 'development' ) {
					$item->setDescription("$resourceType/$resourceGroup/$page/$resourceId");
				}
				if ( $resourceThumbnail != null ) {
					$item->setThumbnail($resourceThumbnail);
				}
				
				$items->append($item);
				
			}
			
			if ( $nextNeeded ) {
				// we need the "previus-page" link
				$item = new X_Page_Item_PItem($this->getId()."-nextpage", X_Env::_("p_filmstream_page_next", ($page + 1)));
				$item->setType(X_Page_Item_PItem::TYPE_CONTAINER)
					->setCustom(__CLASS__.':location', "$resourceType/$resourceGroup/".($page + 1))
					->setLink(array(
						'l'	=>	X_Env::encode("$resourceType/$resourceGroup/".($page + 1))
					), 'default', false);
				$items->append($item);
			}


```

Semplicemente quello che viene fatto è:

  * inserire il pulsante per la pagina precedente se il numero di pagina corrente è maggiore di 1
  * per ogni elemento in matches inserire un elemento nella lista specificando, dove possibile, thumbnail e descrizione
  * aggiungere il pulsante per la pagina successiva se è possibile visualizzare altri elementi dopo questi.

Curiosi di vedere il risultato?

![https://lh6.googleusercontent.com/_U6HIkh_ODAo/TZWFPys9BNI/AAAAAAAAAHA/rBfwgxeqqnM/s400/plugin_0.5.4_tutorial_4.png](https://lh6.googleusercontent.com/_U6HIkh_ODAo/TZWFPys9BNI/AAAAAAAAAHA/rBfwgxeqqnM/s400/plugin_0.5.4_tutorial_4.png)


## Fase 10: cerchiamo i link ai video ##


Ricapitoliamo un po' la situazione: arrivati a questo punto il nostro utente ha selezionato il tipo di categoria, il gruppo di appartenenza, la pagina e il titolo della risorsa. Non ci rimane che cercare i link ai video nella pagina appartenente alla risorsa.

Questo scenario corrisponde al `case 4`:

```

			case 5:
				// we shouldn't be here!
				// if we have 5 pieces (so even $videoId is setted)
				// we should be inside the browse/mode page
				// because the location is about a video URL
			case 4:
				// delegate to fetchVideos
				$this->_fetchVideos($items, $resourceType, $resourceGroup, $page, $resourceId);
				break;


```

Quello che il nostro metodo `_fetchVideos` deve fare è individuare la pagina giusta da leggere (basandosi sulla selezione dell'utente) e scansionarla alla ricerca di link video validi.



```

	
	private function _fetchVideos(X_Page_ItemList_PItem $items, $resourceType, $resourceGroup, $page, $resourceId) {
		
		X_Debug::i("Fetching videos for $resourceType, $resourceGroup, $page, $resourceId");
		
		// as first thing we have to recreate the resource url from resourceId
		$url = self::URL_BASE;
		// in _fetchResources we converted / in : inside $resourceId and removed the last /
		// so to come back to the real address we have to undo this changes
		$url .= str_replace(':', '/', $resourceId) . '/';
		// now url is something like http://film-stream.tv/01/01/2010/resource-name/
		
		// loading page
		$htmlString = $this->_loadPage($url);

		// it's useless to execute pattern search in the whole page
		// so we stip from $htmlString only the usefull part
		$mainContentStart = '<div id="maincontent">';
		$mainContentEnd = '<div id="sidebar">';
		
		$mainContentStart = strpos($htmlString, $mainContentStart);
		if ( $mainContentStart === false ) $mainContentStart = 0;
		$mainContentEnd = strpos($htmlString, $mainContentEnd, $mainContentStart);
		// substr get a substring of $htmlString from $mainContentStart position to $mainContentEnd position - $mainContentStart position (is the fragment length)
		$htmlString = ($mainContentEnd === false ? substr($htmlString, $mainContentStart) : substr($htmlString, $mainContentStart, ($mainContentEnd - $mainContentStart) ) );

		// let's define some pattern
		
		// $ytPattern will try to intercept
		// youtube trailer link 
		// <param name="movie" value="http://www.youtube.com/v/VIDEOID?version=3">
		// match[1] = video id
		$ytPattern = '/<param name\=\"movie\" value\=\"http\:\/\/www\.youtube\.com\/v\/([^\?\"\&]+)([^\>]*)>/';
		
		// $mvPattern will try to intercept
		// megavideo ?v= or ?d= videos
		// <a href="http://www.megavideo.com/?v=VIDEOID">LABEL</a>
		// <a href="http://www.megavideo.com/?d=VIDEOID">LABEL</a>
		// match[1] = v|d
		// match[2] = video id
		// match[4] = label
		$mvPattern = '/href\=\"http\:\/\/www\.megavideo\.com\/\?(v|d)\=([^\"]{8})\"([^\>]*)>([^\<]+)<\/a>/';
		
		$matches = array();
		// first let's search for youtube videos
		
		if ( preg_match_all($ytPattern, $htmlString, $matches, PREG_SET_ORDER) ) {
			
			foreach ($matches as $match) {
				
				$videoId = self::VIDEO_YOUTUBE . ':' . $match[1];
				$videoLabel = X_Env::_("p_filmstream_video_youtubetrailer");
				
				$item = new X_Page_Item_PItem("{$this->getId()}-youtube-$videoId", $videoLabel);
				$item->setIcon('/images/icons/file_32.png')
					->setType(X_Page_Item_PItem::TYPE_ELEMENT)
					->setCustom(__CLASS__.':location', "$resourceType/$resourceGroup/$page/$resourceId/$videoId")
					->setLink(array(
						'action'	=> 'mode',
						'l'	=>	X_Env::encode("$resourceType/$resourceGroup/$page/$resourceId/$videoId")
					), 'default', false);
					
				if ( APPLICATION_ENV == 'development' ) {
					$item->setDescription("$resourceType/$resourceGroup/$page/$resourceId/$videoId");
				}
					
				$items->append($item);
				
			}
			
		} else {
			X_Debug::e("Youtube pattern failure {{$ytPattern}}");
		}
		
		
		if ( preg_match_all($mvPattern, $htmlString, $matches, PREG_SET_ORDER) ) {
			
			foreach ($matches as $match) {
				if ( $match[1] == 'v' ) {
					$videoId = self::VIDEO_MEGAVIDEO . ':' . $match[2];
					$typeLabel = "Megavideo";
				} else {
					$videoId = self::VIDEO_MEGAUPLOAD . ':' . $match[2];
					$typeLabel = "Megaupload";
				}
				$videoLabel = "{$match[4]} [$typeLabel]";
				
				$item = new X_Page_Item_PItem("{$this->getId()}-youtube-$videoId", $videoLabel);
				$item->setIcon('/images/icons/file_32.png')
					->setType(X_Page_Item_PItem::TYPE_ELEMENT)
					->setCustom(__CLASS__.':location', "$resourceType/$resourceGroup/$page/$resourceId/$videoId")
					->setLink(array(
						'action'	=> 'mode',
						'l'	=>	X_Env::encode("$resourceType/$resourceGroup/$page/$resourceId/$videoId")
					), 'default', false);
					
				if ( APPLICATION_ENV == 'development' ) {
					$item->setDescription("$resourceType/$resourceGroup/$page/$resourceId/$videoId");
				}
					
				$items->append($item);
				
			}
			
		} else {
			X_Debug::e("Youtube pattern failure {{$ytPattern}}");
		}
		
	}	

```

In questo caso ho deciso di adottare la tecnica di pattern matching in quanto i link ai video sono abbastanza semplici da intercettare usando dei semplici pattern.

Analizziamo il metodo:

  * nella prima parte provvederemo a ricreare il link della pagina che contiene i video partendo dai parametri memorizzati nella `$location` decisi dall'utente.
  * in seguito leggeremo la pagina inserendo tutto il contenuto in `$htmlString`
  * per velocizzare le funzioni di pattern matching ho deciso di ridurre l'area di ricerca solo alla porzione principale della pagina. Per farlo ho ricercato la stringa `<div id="maincontent">` e la stringa `<div id="sidebar">` e le ho usate come fossero degli estremi. Tutto quello che non è compreso fra questi due estremi viene escluso.
  * a questo punto ho preparato i pattern dopo aver analizzato il codice sorgente di alcune pagine. Ho notato che i trailer da youtube sono tutti uguali nella parte `param` che contiene l'indirizzo al video. Quello che invece ha creato un po' di problemi è la ricerca dei video megavideo/megaupload in quanto per alcuni video il tag `<a>` veniva farcito con attributi anomali (prima e dopo l'href). Vi rimando alla documentazione su Google e Wikipedia (come sempre) per informazioni più dettagliate sul pattern matching.
  * ho poi ricercato all'interno della pagina i video youtube e li ho inseriti alla mia lista di oggetti `X_Page_Item_PItem`
  * ho ripetuto il processo analogo per quanto riguarda i video megavideo e megaupload

Come per il caso precedente gestito tramite XPath, anche il Pattern Matching non è esente da errori o imprecisioni. Ottenere quindi il 100% di riscontri risulta abbastanza difficile.


Una cosa importante da notare in questo caso è questa:

```
					->setLink(array(
						'action'	=> 'mode',
						'l'	=>	X_Env::encode("$resourceType/$resourceGroup/$page/$resourceId/$videoId")
					), 'default', false);

```

Durante la scrittura dei parametri del link nei casi precedenti ci siamo sempre limitati ad aggiornare (si, aggiornare. Tutte le i parametri non modificati venivano mantenuti da una sessione all'altra) i contenuto del parametro `location`. In questo caso pero abbiamo impostato anche il parametro `action` impostandolo su `mode`. Il controller rimane sempre inalterato (quindi sempre `browseController`, ma il metodo eseguito per gestire la prossima richiesta sarà `modeAction` e non più `shareAction`.

Questo ci consente di indicare al sistema che se il pulsante venisse cliccato dall'utente, il collegamento dovrebbe essere gestito come una risorsa riproducibile.
**Attenzione**: risorsa riproducibile non indica un video ad un link, ma solo che la $location potrà essere convertita in un collegamento ad un video usando eventualmente il metodo `resolveLocation` del provider (se presente).

Da questo punto in poi quindi, il trigger `getShareItems` non verrà più invocato.

Salviamo le modifiche e andiamo a vedere quello cosa succede.

![https://lh4.googleusercontent.com/_U6HIkh_ODAo/TZWeTd9xftI/AAAAAAAAAHE/Fl1DaUMHigE/s640/plugin_0.5.4_tutorial_5.png](https://lh4.googleusercontent.com/_U6HIkh_ODAo/TZWeTd9xftI/AAAAAAAAAHE/Fl1DaUMHigE/s640/plugin_0.5.4_tutorial_5.png)

Nell'immagine c'e' il confronto fra quello che viene mostrato tramite vlc-shares (sulla sinistra) e la pagina originale cosi come mostrata su filmstream (sulla destra). Non c'è male, non credete?

_Nota:_ volevo solo far notare che i video 2 e 3 della prima stagione non vengono intercettati semplicemente perche non sono link a megavideo o megaupload, ma a veoh (che non è supportato, per ora almeno).

## Fase 11: rendiamo il video riproducibile (parte 1) ##

Arrivati a questo punto, le maggiori difficoltà sono state superate. Quello che dobbiamo fare ora è permettere al video che è stato selezionato di essere riprodotto.

Facciamo un sunto della situazione: per quanto implementato fino ad ora l'utente può sfogliare i contenuti e selezionarne uno specifico.

Le informazioni sul video specifico vengono memorizzate sempre in `location`.

Abbiamo trattato prima il senso dell'interfaccia `X_VlcShares_Plugins_ResolverInterface` e del metodo `resolveLocation`. Quello che dobbiamo fare è implementarlo per permettere al sistema e agli altri plugin ottenere un link reale ad uno stream video.

Facciamo un piccolo riepilogo anche su quello che è il formato di `$location`. Quando siamo nella pagina `browse/mode` possiamo dare per scontato che la location sia completa di tutti i parametri. Il suo formato sarà:

```
TIPO_RISORSA/GRUPPO_RISORSA/PAGINA/ID_RISORSA/ID_VIDEO
```

Durante il ritrovamento dei video, abbiamo inoltre fatto un distinguo sul tipo di video possibile indicando l'`ID_VIDEO` in questo formato:

```
TIPO_SERVIZIO:ID_VIDEO_NEL_SERVIZIO
```

Durante l'implementazione di `resolveLocation` dobbiamo tenere conto di questo.

Proviamo ad implementare il metodo in pseudo-codice.

```

location_divisa <- dividi( location, '/' )

if ( il numero di pezzi di location_divisa != 5 ) then
   return errore: non è possibile risolvere perche la location non è completa
endif 

id_video <- location_divisa[5]

id_video_diviso <- dividi( id_video, ':')

if ( il numero di pezzi di id_video_diviso != 2 ) then
   return errore: il video id non è ben formato
endif

tipo_video <- id_video_diviso[1]

id_video_vero <- id_video_diviso[2]

switch ( tipo_video )

    == youtube: return l'indirizzo del video da youtube
    
    == megavideo: return l'indirizzo del video da megavideo

    == megaupload: return l'indirizzo del video da megaupload

endswitch

return error: il tipo_video non è stato riconosciuto

```

Non sembra molto complesso, no? Implementiamolo.

```

	/**
	 * @see X_VlcShares_Plugins_ResolverInterface::getLocation()
	 */
	function resolveLocation($location = null) {

		if ( $location == '' || $location == null ) return false;
		
		if ( array_key_exists($location, $this->cachedLocation) ) {
			return $this->cachedLocation[$location];
		}
		
		X_Debug::i("Requested location: $location");

		$split = $location != '' ? @explode('/', $location, 5) : array();
		@list($resourceType, $resourceGroup, $page, $resourceId, $videoId) = $split;

		// videoId overwritted by real videoId
		@list($videoType, $videoId) = @explode(':', $videoId, 2);

		X_Debug::i("Type: $resourceType, Group: $resourceGroup, Page: $page, Resource: $resourceId, VideoType: $videoType, VideoId: $videoId");
		
		if ( $videoType == null || $videoId == null ) {
			// location isn't a valid video url, so we return fals
			// and insert the query result in the cache
			$this->cachedLocation[$location] = false;
			return false;	
		}

		$return = false;
		switch ($videoType) {
			
			case self::VIDEO_MEGAVIDEO:
				try {
					/* @var $megavideoHelper X_VlcShares_Plugins_Helper_Megavideo */
					$megavideoHelper = $this->helpers('megavideo');
					
					X_Debug::i("Megavideo ID: $videoId");
					if ( $megavideoHelper->setLocation($videoId)->getServer() ) {
						$return = $megavideoHelper->getUrl();
					}
				} catch (Exception $e) {
					X_Debug::e("Megavideo helper isn't installed or enabled: {$e->getMessage()}");
				}
				break;
				
			case self::VIDEO_MEGAUPLOAD:				
				try {
					/* @var $megauploadHelper X_VlcShares_Plugins_Helper_Megaupload */
					$megauploadHelper = $this->helpers('megaupload');
					
					X_Debug::i("Megaupload ID: $videoId");
					if ( $megauploadHelper->setMegauploadLocation($videoId)->getServer() ) {
						$return = $megauploadHelper->getUrl();
					}
				} catch (Exception $e) {
					X_Debug::e("Megaupload helper isn't installed or enabled: {$e->getMessage()}");
				}
				break;
			case self::VIDEO_YOUTUBE:
				try {
					/* @var $youtubeHelper X_VlcShares_Plugins_Helper_Youtube */
					$youtubeHelper = $this->helpers('youtube');
					/* @var $youtubePlugin X_VlcShares_Plugins_Youtube */
					$youtubePlugin = X_VlcShares_Plugins::broker()->getPlugins('youtube');
					
					X_Debug::i("Youtube ID: $videoId");

					// THIS CODE HAVE TO BE MOVED IN YOUTUBE HELPER
					// FIXME
					$formats = $youtubeHelper->getFormatsNOAPI($videoId);
					$returned = null;
					$qualityPriority = explode('|', $youtubePlugin->config('quality.priority', '5|34|18|35'));
					foreach ($qualityPriority as $quality) {
						if ( array_key_exists($quality, $formats)) {
							$returned = $formats[$quality];
							X_Debug::i('Video format selected: '.$quality);
							break;
						}
					}
					if ( $returned === null ) {
						// for valid video id but video with restrictions
						// alternatives formats can't be fetched by youtube page.
						// i have to fallback to standard api url
						$apiVideo = $youtubeHelper->getVideo($videoId);
						
						foreach ($apiVideo->mediaGroup->content as $content) {
							if ($content->type === "video/3gpp") {
								$returned = $content->url;
								X_Debug::w('Content restricted video, fallback to api url:'.$returned);
								break;
							}
						}
	
						if ( $returned === null ) {
							$returned = false;
						}
					}					
					$return = $returned;
					
				} catch (Exception $e) {
					X_Debug::e("Youtube helper/plugin isn't installed or enabled: {$e->getMessage()}");
				}
				break;
				
		}
		
		$this->cachedLocation[$location] = $return;
		return $return;
	
	}

```

L'indirizzo reale dello stream partendo da un ID viene gestito all'interno dello switch e fa uso del sistema di Helper. Questo ci permette di evitare di dover riscrivere codice specifico per ogni plugin che utilizza questo genere di Hoster, ma di fatto ci linka alla necessità di dover inserire all'interno delle dipendenze di questo plugin youtube e megavideo.

Per poter provare questo codice è quindi necessario andare ad aggiungere negli `extraPlugin` da caricare anche le chiavi `youtube` e `megavideo` (questo passaggio è stato spiegato nella  fase 6).


## Fase 12: rendiamo il video riproducibile (parte 2) ##

Implementando `resolveLocation` abbiamo offerto un modo per ottenere l'indirizzo del vero video selezionato. Sfrutteremo quanto fatto per aggiungere il collegamento "Riproduzione diretta" che consentirà a WiiMC o ai browser di riprodurre il filmato senza richiedere l'intervendo di VLC per la transcodifica.

Come ho già detto, selezionando un video, il controllo dell'applicazione passa al controller `BrowseController` e all'azione `modeAction`.

Questa azione permette di interagire con il sistema tramite questi trigger:
  * `preGetModeItems`
  * `getModeItems`
  * `postGetModeItems`
  * `filterModeItems`

Il funzionamento di questi trigger è analogo a quelli della pagina `browse/share`.

Quello che stiamo per fare è inserire un nuovo elemento di nome "Riproduzione Diretta" nella parte alta della pagina usando il trigger `preGetModeItems`.

Aggiungiamo priorità al trigger nel costruttore e procediamo con l'implementazione.

```

	function __construct() {
		$this->setPriority('getCollectionsItems');
		$this->setPriority('getShareItems');
		$this->setPriority('preGetModeItems');
	}

```

```

	/**
	 *	Add button -watch stream directly-
	 * 
	 * @param string $provider
	 * @param string $location
	 * @param Zend_Controller_Action $controller
	 */
	public function preGetModeItems($provider, $location, Zend_Controller_Action $controller) {

		if ( $provider != $this->getId()) return;
		
		X_Debug::i("Plugin triggered");
		
		$url = $this->resolveLocation($location);
		
		if ( $url ) {
			$link = new X_Page_Item_PItem('core-directwatch', X_Env::_('p_filmstream_watchdirectly'));
			$link->setIcon('/images/icons/play.png')
				->setType(X_Page_Item_PItem::TYPE_PLAYABLE)
				->setLink($url);
			return new X_Page_ItemList_PItem(array($link));
		} else {
			// if there is no link, i have to remove start-vlc button
			// and replace it with a Warning button
			
			X_Debug::i('Setting priority to filterModeItems');
			$this->setPriority('filterModeItems', 99);
			
			$link = new X_Page_Item_PItem('megavideo-warning', X_Env::_('p_filmstream_invalidlink'));
			$link->setIcon('/images/msg_error.png')
				->setType(X_Page_Item_PItem::TYPE_ELEMENT)
				->setLink(array (
					'controller' => 'browse',
					'action' => 'share',
					'p'	=> $this->getId(),
					'l' => X_Env::encode($this->getParentLocation($location)),
				), 'default', true);
			return new X_Page_ItemList_PItem(array($link));

		}
	}
```

Il metodo funziona in modo abbastanza semplice: utilizza `resolveLocation` per ottenere il link all'url del video e aggiunge (se il link è valido) un elemento alla lista di tipo `X_Page_Item_PItem::TYPE_PLAYABLE` con l'indirizzo del video.
In questo caso, utilizzeremo il tipo `TYPE_PLAYABLE` per indicare al sistema che il tipo di link è proprio un URL di un video.

Nel caso in cui `resolveLocation` non indirizzo valido, provvediamo ad aggiungere priorità al trigger `filterModeItems` che verrà chiamato in seguito per rimuovere il pulsante `START VLC STREAM` dalla lista. In seguito aggiungiamo un elemento che indichi all'utente che il video non è valido.
Utilizzeremo il metodo `getParentLocation` per ottenere l'indirizzo padre del video selezionato.

Procediamo quindi implementando `getParentLocation`. La sua implementazione sarà abbastanza semplice: il metodo dovrà limitarsi a rimuovere il parametro più a destra contenuto nella `location` con l'eccezione del quarto (quello dell'ID\_RISORSA), in cui elimineremo due parametri. Questo ci consentirà di passare direttamente dalla pagina contenente i titoli a quella di selezione del gruppo senza dover passare dalla prima pagina.


```

	/**
	 * @see X_VlcShares_Plugins_ResolverInterface::getParentLocation()
	 */
	function getParentLocation($location = null) {
		if ( $location == '' || $location == null ) return false;
		
		$exploded = explode('/', $location);
		
		// type/group/page/idres/idvideo
		if ( count($exploded) == 4 ) {
			// i have to add an extra pop
			// to jump from idres page to group page
			array_pop($exploded);
		} 
		
		array_pop($exploded);
		
		if ( count($exploded) >= 1 ) {
			return implode('/', $exploded);
		} else {
			return null;
		}			
	}


```

Curiosi di vedere il risultato?

![https://lh4.googleusercontent.com/_U6HIkh_ODAo/TZW97TC1o0I/AAAAAAAAAHI/edKAR-zBEJA/s800/plugin_0.5.4_tutorial_6.png](https://lh4.googleusercontent.com/_U6HIkh_ODAo/TZW97TC1o0I/AAAAAAAAAHI/edKAR-zBEJA/s800/plugin_0.5.4_tutorial_6.png)

## Fase 13: filtriamo un elemento da una lista ##

Ho deciso di chiamare questa fare in maniera cosi generica semplicemente perchè quanto vedremo ora può essere applicato a tutti i trigger di tipo `filterXXX` in maniera analoga.

Nello specifico utilizzero il trigger `filterModeItem` per rimuovere il pulsante "START VLC STREAM" nel caso in cui il video non sia valido.


Poichè l'attivazione del trigger è stata gestita in modo che venga assegnata priorità solo se già si è a conoscenza del fatto che il video non è valido, il nostro unico obbiettivo sarà quello di individuare l'elemento da filtrare e rimuoverlo senza dover indugiare ulteriormente per verificare la validità del video.

Utilizzeremo l'`id` associato all'item `START VLC STREAM` per identificarlo e rimuoverlo.


```

	/**
	 * Remove vlc-play button if location is invalid
	 * @param X_Page_Item_PItem $item,
	 * @param string $provider
	 * @param Zend_Controller_Action $controller
	 */
	public function filterModeItems(X_Page_Item_PItem $item, $provider,Zend_Controller_Action $controller) {
		if ( $item->getKey() == 'core-play') {
			X_Debug::i('plugin triggered');
			X_Debug::w('core-play flagged as invalid because the link is invalid');
			return false;
		}
	}

```

## Fase 14: suggeriamo l'url del video a VLC ##

Fino a questo punto siamo riusciti a fornire alle device un link di collegamento diretto. Ma cosa succede se l'utente necessita di utilizzare la transcodifica?
Cliccando sul pulsante "START VLC STREAM" viene invocata la pagina `browse/stream` che ha il compito di preparare tutti i parametri da fornire a VLC e avviarlo.

E' compito del provider quello di fornire il parametro `source` a VLC in modo che corrisponda all'url del video. Utilizzeremo il trigger `preRegisterVlcArgs` per intercettare il momento di inserimento dei parametri. Ormai dovrebbe essere chiaro cosa fare: come sempre, aggiungere priorità al trigger nel costruttore e implementare la funzione.


```

	
	function __construct() {
		$this->setPriority('getCollectionsItems');
		$this->setPriority('getShareItems');
		$this->setPriority('preGetModeItems');
		$this->setPriority('preRegisterVlcArgs');
	}

```


```

	/**
	 * This hook can be used to add low priority args in vlc stack
	 * 
	 * @param X_Vlc $vlc vlc wrapper object
	 * @param string $provider id of the plugin that should handle request
	 * @param string $location to stream
	 * @param Zend_Controller_Action $controller the controller who handle the request
	 */
	public function preRegisterVlcArgs(X_Vlc $vlc, $provider, $location, Zend_Controller_Action $controller) {
	
		// this plugin inject params only if this is the provider
		if ( $provider != $this->getId() ) return;
		// i need to register source as first, because subtitles plugin use source
		// for create subfile
		X_Debug::i('Plugin triggered');
		$location = $this->resolveLocation($location);
		if ( $location !== null ) {
			$vlc->registerArg('source', "\"$location\"");			
		} else {
			X_Debug::e("No source o_O");
		}
	}

```

Con questa fase abbiamo completato la trattazione delle problematiche generali relative alla scrittura di plugin DataProvider.

Le fasi che seguono servono solo per aggiungere qualche funzionalità opzionale.


# Funzionalità aggiuntive #

Apporteremo adesso qualche modifica finalizzata a migliorare qualche dettaglio e a fare una carrellata delle funzioni più comuni messe a disposizione dei plugin dal sistema di base.

## Utilizzo del multilinguaggio e delle traduzioni ##

La prima cosa che viene chiesta durante l'installazione di vlc-shares è il linguaggio da utilizzare. Mi pare evidente che questo sia dovuto al fatto che vlc-shares ha il supporto per il multi linguaggio. Questo supporto è integrato fra le funzioni del core ed è anche a disposizione dei plugin.

Sicuramente avrete notato come, durante le 10 fasi precedenti, tutte le etichette per gli elementi che abbiamo inserito nelle liste avevano strani nomi ed erano tutte inserite all'interno di una chiamata al metodo statico `X_Env::_()`. Tramite questo metodo infatti, vlc-shares fornisce le funzionalità di traduzione. Inserendo come parametro una chiave, la funzione restituira come output il valore tradotto in base alla lingua selezionata. Tutte le traduzioni di base di vlc-shares sono inserite nei file di linguaggio delle varie lingue (ad esempio it\_IT.ini, en\_GB.ini o es\_ES.ini). Ogni plugin avrà a disposizione tutte le chiavi inserite nei file.

Chiaramente non siamo limitati a quelle. Il sistema fornisce un helper per il caricamento di file di traduzione personalizzati. Tramite questo, un plugin può decidere di aggiungere un file di traduzione personale che verrà integrato alle chiavi gia lette per la lingua corrente.

Vediamo come attivare il supporto al multi linguaggio per il nostro plugin.

Ricordate l'elenco degli elementi che ho fatto inserire durante la creazione della struttura dei file e delle directory del plugin? No? Ve lo ripeto io.

```

php create-plugin.php -k filmstream -n FilmStream -e plugins,languages,images

```

In quel modo avevano indicato che eravamo interessati alla preparazione della struttura per il linguaggi.

Lo script aveva preparato per noi una directory `/languages` con all'interno i due file per le lingue italiano e inglese.

Quello che dobbiamo fare ora è inserire all'interno dei due file le varie chiavi con la traduzione. Il formato dei file ini utilizzati per le traduzioni è il seguente:

```

; Questo è un commento
questa_e_una_chiave="Questo e' il valore della chiave"

```

Quello che dobbiamo fare ora è rintracciare tutte le chiavi che abbiamo utilizzato nel plugin e inserirle all'interno del file di traduzione con le relative traduzioni.
Questo ad esempio è il file di traduzione (parziale) del nostro plugin per la lingua inglese.

```

; X_VlcShares_Plugins_FilmStream.en_GB.ini
; VLC-SHARES: english translation file for X_VlcShares_Plugins_FilmStream plugin
; version 0.1

p_filmstream_plglabel="FilmStream.tv"
p_filmstream_plgdesc="Allow to watch videos from FilmStream.tv site (Italian)"

p_filmstream_collectionindex="FilmStream.tv Channel"
p_filmstream_collectionindex_desc="Browse FilmStream.tv [Italian]"
p_filmstream_watchdirectly="Watch directly"
p_filmstream_invalidlink="Invalid video link"
p_filmstream_type_movies="Movies"
p_filmstream_type_tvshows="TV Series"
p_filmstream_type_anime="Anime"
p_filmstream_page_previous="<<< Previus Page (%s) <<<"
p_filmstream_page_next=">>> Next Page (%s) >>>"
p_filmstream_video_youtubetrailer="Trailer from YouTube"

```

L'unica nota da aggiungere a quanto detto è quella relativa alla chiave `p_filmstream_page_next`: nella traduzione si fa uso del segnaposto `%s`. La funzione `X_Env::_()` accetta, oltre alla chiave, un numero arbitrario di parametri che verranno sostituiti ad eventuali segnaposto presenti nelle traduzioni. `X_Env::_()` utilizza internamento la funzione di php `sprintf`, quindi ogni notazione utilizzabile con quest'ultima è ammessa.

Una volta preparati i file di traduzione è necessario che il plugin fornisca comunicazione al sistema per richiederne il caricamento.

C'e' un trigger predisposto a questo utilizzo: `gen_beforeInit`. Questo trigger viene caricato da qualsiasi pagina, sia nella parte di riproduzione, che nelle pagine di amministrazione. E' il posto ideale quindi per poter inserire la nostra richiesta di caricamento.

Come detto, è l'helper `language` che ci fornisce il modo di aggiungere il nostro file di traduzione. In questo modo:

```

	/**
	 * Inizialize translator for this plugin
	 * @param Zend_Controller_Action $controller
	 */
	function gen_beforeInit(Zend_Controller_Action $controller) {
		$this->helpers()->language()->addTranslation(__CLASS__);
	}

```

Ricordate, chiaramente di modificare il costruttore e aggiungere priorità al trigger `gen_beforeInit`.

```

	function __construct() {
		$this->setPriority('getCollectionsItems');
		$this->setPriority('getShareItems');
		$this->setPriority('preGetModeItems');
		$this->setPriority('preRegisterVlcArgs');
		$this->setPriority('gen_beforeInit');
	}


```

Al metodo `addTranslation` indicheremo semplicemente il prefisso del nostro file di traduzione, al quale verrà aggiunto per completare il tipo di linguaggio attualmente in uso. Per essere più chiaro: se nel sistema è in uso il file di traduzione `en_GB.ini`, il nostro file di traduzione del plugin sarà `X_VlcShares_Plugins_FilmStream.en_GB.ini`.

Ora è tutto pronto, manca solo un piccolo dettaglio: nel file `manifest.xml` relativo al plugin è necessario specificare l'elenco dei file di traduzione del plugin.

Aggiungiamo quindi nel file `manifest.xml` l'elenco dei nostri file come figli del nodo `<files>`, in questo modo

```

		<languages>
			<file>X_VlcShares_Plugins_FilmStream.en_GB.ini</file>
			<file>X_VlcShares_Plugins_FilmStream.it_IT.ini</file>
		</languages>
```

Con questi semplici passaggi abbiamo aggiunto il supporto al multi linguaggio al nostro plugin.

Curiosi di vedere il risultato?

![https://lh4.googleusercontent.com/_U6HIkh_ODAo/TZYLb39-obI/AAAAAAAAAHM/ITSGZGDLJsM/s640/plugin_0.5.4_tutorial_7.png](https://lh4.googleusercontent.com/_U6HIkh_ODAo/TZYLb39-obI/AAAAAAAAAHM/ITSGZGDLJsM/s640/plugin_0.5.4_tutorial_7.png)


## Utilizzo del sistema di configurazioni ##

Un altro servizio offerto da vlc-shares è quello di accesso alle configurazioni per-plugin. Durante l'installazione, il plugin può aggiungere un set di configurazioni all'interno della tabella principale nella sezione plugin. In questo modo, tutte le configurazioni riguardanti la propria chiave e inserite all'interno della sezione plugin verranno offerte in fase di inizializzazione del plugin.

Il nostro plugin potrebbe benissimo fare a meno di questo passaggio, ma colgo l'occasione per utilizzarlo in modo da potervelo presentare brevemente.

L'elenco delle configurazioni va aggiunto al database nello script di installazione `install.sql` e bisogna assicurarsi di non lasciare alcuna traccia dopo la disinstallazione. Dobbiamo quindi prenderci carico di eliminare tutti gli elementi aggiunti nel relativo file `uninstall.sql`

Nel nostro file di installazione aggiungeremo un solo elemento di configurazione che ci permetterà di attivare o disattivare l'offuscamento dello user-agent durante le richieste al sito filmstream.tv

```

INSERT INTO configs ( `key`, `value`, `default`, `section`, type, label, description, class ) VALUES (
	'filmstream.hide.useragent', '0', '0', 'plugins', 3, 'p_filmstream_conf_hideuseragent_label', 'p_filmstream_conf_hideuseragent_desc',	'');


UPDATE plugins SET enabled=1 WHERE key = "filmstream";

```

I tipo di configurazione riconosciuti sono:

  * **0**: input di tipo text
  * **1**: select
  * **2**: textarea
  * **3**: booleano (si/no)
  * **4**: file (attualmente previsto ma non ancora supportato)
  * **5**: radio

Potremo usare le configurazioni inserite tramite il metodo `X_VlcShares_Plugins_Abstract::config()`. Dovremo indicare al metodo la `key` della nostra configurazione, escludendo la prima parte che corrisponde alla chiave del plugin.

Nel plugin facciamo uso di questa configurazione nel metodo `_loadPage()`:


```

	private function _loadPage($uri) {

		X_Debug::i("Loading page $uri");
		
		$http = new Zend_Http_Client($uri, array(
			'maxredirects'	=> $this->config('request.maxredirects', 10),
			'timeout'		=> $this->config('request.timeout', 25)
		));
		
		$http->setHeaders(array(
			$this->config('hide.useragent', false) ? 'User-Agent: vlc-shares/'.X_VlcShares::VERSION .' filmstream/'.self::VERSION_CLEAN : 'User-Agent: Mozilla/5.0 (X11; Linux i686; rv:2.0.1) Gecko/20101019 Firefox/4.0.1',
		));
		
		$response = $http->request();
		$htmlString = $response->getBody();
		
		return $htmlString;
	}

```

Ricordate di aggiungere le rispettive chiavi nei file del linguaggio per label e descrizione delle configurazioni.

Per poter rendere modificabili le configurazioni all'utente, vlc-shares fornisce una pagina che permette di visualizzare tutte le configurazioni relative ad un plugin e modificarle, in maniera totalmente indipendente da qualsiasi interazione del plugin. La pagina che offre questo servizio è `config/index` e per indicare quali configurazioni bisogna visualizzare è sufficiente fornire il parametro `key` con valore uguale alla chiave del plugin al quale appartengono le configurazioni.

Il plugin deve solo provvedere a fornire all'utente un link con il quale accedere alla pagina. Il modo ideale per farlo sarebbe aggiungendo un pulsante alla dashboard di vlc-shares tramite il trigger `getIndexManageLink`.

```

	/**
	 * Add the link for -manage-filmstream-
	 * @param Zend_Controller_Action $this
	 * @return X_Page_ItemList_ManageLink
	 */
	public function getIndexManageLinks(Zend_Controller_Action $controller) {

		$link = new X_Page_Item_ManageLink($this->getId(), X_Env::_('p_filmstream_mlink'));
		$link->setTitle(X_Env::_('p_filmstream_managetitle'))
			->setIcon('/images/filmstream/logo.png')
			->setLink(array(
					'controller'	=>	'config',
					'action'		=>	'index',
					'key'			=>	'filmstream'
			), 'default', true);
		return new X_Page_ItemList_ManageLink(array($link));
		
	}


```

Ricordate di aggiungere priorità al trigger nel costruttore e aggiungere le chiavi di traduzione ai file.

Pronti per i risultati?

![https://lh4.googleusercontent.com/_U6HIkh_ODAo/TZYWcBVpidI/AAAAAAAAAHQ/NS5UhAm_HSY/s800/plugin_0.5.4_tutorial_8.png](https://lh4.googleusercontent.com/_U6HIkh_ODAo/TZYWcBVpidI/AAAAAAAAAHQ/NS5UhAm_HSY/s800/plugin_0.5.4_tutorial_8.png)

## Visualizzare una notifica per la mancanza di dipendenze ##

Visto che il nostro plugin necessita dei plugin youtube e megavideo per poter vedere i video, è bene notificare all'utente un errore nel caso in cui uno di questi plugin non sia installato o non sia attivo.

Per farlo, il sistema ci offre due possibilità: o tramite un test nella pagina System Test oppure aggiungendo un messaggi di notifica nella dashboard di vlc-shares. Noi implementeremo la seconda strada.

Lo faremo bevemente, in quanto il vecchio tutorial per i plugin della versione 0.5.1 tratta proprio questo stesso argomento.

Il trigger che useremo è `getIndexMessages()`. La funzione dovrà verificare se una delle due dipendenze è mancante, e visualizzare il messaggio in quel caso.

```


	/**
	 * Show an error message if one of the plugin dependencies is missing
	 * @param Zend_Controller_Action $this
	 * @return X_Page_ItemList_Message
	 */
	public function getIndexMessages(Zend_Controller_Action $controller) {
		$messages = new X_Page_ItemList_Message();
		if ( !X_VlcShares_Plugins::broker()->isRegistered('megavideo') ) { 
			$message = new X_Page_Item_Message($this->getId(), X_Env::_('p_filmstream_warning_nomegavideo'));
			$message->setType(X_Page_Item_Message::TYPE_ERROR);
			$messages->append($message);
		}
		if ( !X_VlcShares_Plugins::broker()->isRegistered('youtube') ) { 
			$message = new X_Page_Item_Message($this->getId(), X_Env::_('p_filmstream_warning_noyoutube'));
			$message->setType(X_Page_Item_Message::TYPE_WARNING);
			$messages->append($message);
		}
		return $messages;
	}


```

# Conclusioni #

## Pacchettizzazione ##

Una volta conclusa la nostra fase di sviluppo, è necessario testare il plugin installandolo tramite il normale plugin installer in una installazione ordinaria di vlc-shares.

Le versioni di sviluppo di vlc-shares offrono uno script in grado di creare un pacchetto installabile di un plugin in maniera automatica.

Per poterlo utilizzare è sufficiente aprire un console nella directory **VLCSHARESDEV\_BASEDIR**`/scripts/` e digitare

Su Windows:

```
php.bat build.php -p filmstream
```

Su Linux:

```
php build.php -p filmstream
```

I pacchetti verranno creati nella directory **VLCSHARESDEV\_BASEDIR**`/dist/`

Buona installazione.

## Problemi? Volete ripristinare la vostra versione di sviluppo? ##

Durante lo sviluppo, nel caso per una qualsiasi motivazione vogliate ripristinare la versione di sviluppo rimuovendo i file linkati/copiati dagli script `dev_bootstrap.php` e ripristinare la versione originale del database, vi basterà eseguire questi script tramite una console aperta nella directory **VLCSHARESDEV\_BASEDIR**`/scripts/`

Su Windows:

```
php.bat load.sqlite.php --withdata
php.bat cleanup.php
```

Su Linux:

```
php load.sqlite.php --withdata
php cleanup.php
```

## Note conclusive ##

Ho cercato di dare un'idea di quello che lo comporta lo sviluppo di un plugin DataProvider. Chiaramente, le problematiche possono essere differenti in base al sito target che vogliamo integrare.
<?php
/**
 * ReadyScript (http://readyscript.ru).
 *
 * @copyright Copyright (c) ReadyScript lab. (http://readyscript.ru)
 * @license http://readyscript.ru/licenseAgreement/
 */

namespace Article\Model\Orm;

use Article\Model\CatApi;
use Catalog\Model\Orm\Product;
use Photo\Model\Orm\Image;
use Photo\Model\PhotoApi;
use RS\Application\Auth;
use RS\Module\Manager as ModuleManager;
use RS\Orm\OrmObject;
use RS\Orm\Request as OrmRequest;
use RS\Orm\Type;
use RS\Router\Manager as RouterManager;
use Tags\Model\Orm\Link as TagLink;
use Users\Model\Orm\User;

/**
 * --/--.
 *
 * @property int    $id                    Уникальный идентификатор (ID)
 * @property int    $site_id               ID сайта
 * @property string $title                 Название
 * @property string $alias                 Псевдоним(Ан.яз)
 * @property string $content               Содержимое
 * @property int    $parent                Рубрика
 * @property string $dateof                Дата и время
 * @property string $image                 Картинка
 * @property int    $user_id               Автор
 * @property float  $rating                Средний балл(рейтинг)
 * @property int    $comments              Кол-во комментариев к статье
 * @property int    $public                Публичный
 * @property string $attached_products     Прикреплённые товары
 * @property array  $attached_products_arr
 * @property string $short_content         Краткий текст
 * @property string $meta_title            Заголовок
 * @property string $meta_keywords         Ключевые слова
 * @property string $meta_description      Описание
 *                                         --\--
 */
class Article extends OrmObject
{
    const MAX_RATING  = 5, //Максимальный рейтинг статьи
        IMAGES_TYPE   = 'article',
        TAG_TYPE      = 'article';

    protected $attach_products;

    //Прикреплённые товары
        protected $fast_mark_attached_products_use; //Флаг используются ли прикреплённые товары

    protected static $table = 'article';

    protected function _init()
    {
        parent::_init()->append([
            t('Основные'),
                    'site_id' => new Type\CurrentSite(),
                    'title'   => new Type\Varchar([
                        'maxLength'   => '150',
                        'description' => t('Название'),
                        'Checker'     => ['chkEmpty', t('Необходимо заполнить поле название')],
                        'attr'        => [[
                            'data-autotranslit' => 'alias',
                        ]],
                    ]),
                    'alias' => new Type\Varchar([
                        'maxLength'   => '150',
                        'index'       => true,
                        'description' => t('Псевдоним(Ан.яз)'),
                        'Checker'     => ['chkalias', null],
                        'meVisible'   => false,
                    ]),
                    'content' => new Type\Richtext([
                        'description' => t('Содержимое'),
                    ]),
                    'parent' => new Type\Integer([
                        'index'       => true,
                        'description' => t('Рубрика'),
                        'tree'        => [[new CatApi(), 'staticTreeList'], 0, ['' => t('- Верхний уровень -')]],
                    ]),
                    'dateof' => new Type\Datetime([
                        'maxLength'   => '19',
                        'description' => t('Дата и время'),
                        'index'       => true,
                    ]),
                    'image' => new Type\Image([
                        'maxLength'        => '255',
                        'max_file_size'    => 10000000,
                        'allow_file_types' => ['image/pjpeg', 'image/jpeg', 'image/png', 'image/gif'],
                        'description'      => t('Картинка'),
                    ]),
                    'user_id' => new Type\User([
                        'description' => t('Автор'),
                    ]),
                    'rating' => new Type\Decimal([
                        'maxLength'   => '3',
                        'decimal'     => '1',
                        'default'     => 0,
                        'visible'     => false,
                        'description' => t('Средний балл(рейтинг)'),
                        'hint'        => t('Расчитывается автоматически, исходя из поставленных оценок, если установлен блок комментариев на странице статьи.'),
                    ]),
                    'comments' => new Type\Integer([
                        'maxLength'   => '11',
                        'description' => t('Кол-во комментариев к статье'),
                        'default'     => 0,
                        'visible'     => false,
                    ]),
                    'public' => new Type\Integer([
                        'description'  => t('Публичный'),
                        'maxLength'    => 1,
                        'checkboxView' => [1, 0],
                        'allowEmpty'   => false,
                        'default'      => 1,
                    ]),
                    'attached_products' => new Type\Varchar([
                        'maxLength'   => 4000,
                        'description' => t('Прикреплённые товары'),
                        'visible'     => false,
                    ]),
                    'attached_products_arr' => new Type\ArrayList([
                        'visible'    => false,
                        'appVisible' => true,
                    ]),
            t('Расширенные'),
                    'short_content' => new Type\Richtext([
                        'description' => t('Краткий текст'),
                    ]),

            t('Мета тэги'),
                    'meta_title' => new Type\Varchar([
                        'maxLength'   => 1000,
                        'description' => t('Заголовок'),
                    ]),
                    'meta_keywords' => new Type\Varchar([
                        'maxLength'   => 1000,
                        'description' => t('Ключевые слова'),
                    ]),
                    'meta_description' => (new Type\Varchar([
                        'description' => t('Описание'),
                    ]))->setViewAsTextarea(1000),
            t('Фото'),
                    '_photo_' => new Type\UserTemplate('%article%/form/article/photos.tpl'),
            '_tmpid'          => new Type\MixedType(),

        ]);

        if (ModuleManager::staticModuleExists('catalog')) {
            $this->getPropertyIterator()->append([
                t('Прикреплённые товары'),
                    '_attached_products_' => new Type\UserTemplate('%article%/form/products/attachedproducts.tpl'),
            ]);
        }

        //Включаем в форму hidden поле id.
        $this['__id']->setVisible(true);
        $this['__id']->setMeVisible(false);
        $this['__id']->setHidden(true);

        $this->addIndex(['site_id', 'parent']);
        $this->addIndex(['site_id', 'parent', 'alias'], self::INDEX_UNIQUE);
    }

    /**
     * Возвращает отладочные действия, которые можно произвести с объектом
     *
     * @return \RS\Debug\Action\AbstractAction[]
     */
    public function getDebugActions()
    {
        return [
            new \RS\Debug\Action\Edit(RouterManager::obj()->getAdminPattern('edit', [':id' => '{id}'], 'article-ctrl')),
            new \RS\Debug\Action\Delete(RouterManager::obj()->getAdminPattern('del', [':chk[]' => '{id}'], 'article-ctrl')),
        ];
    }

    /**
     * Вызывается после загрузки объекта.
     */
    public function afterObjectLoad()
    {
        if (!empty($this['attached_products'])) {
            $this['attached_products_arr'] = unserialize($this['attached_products']);
        }
    }

    public function beforeWrite($flag)
    {
        //Прикреплённые товары
        $this['attached_products'] = serialize($this['attached_products_arr']);

        if ($this['id'] < 0) {
            $this['_tmpid'] = $this['id'];
            unset($this['id']);
        }

        if (empty($this['alias'])) {
            $this['alias'] = null;
        }
        if (empty($this['user_id'])) {
            $this['user_id'] = Auth::getCurrentUser()->id;
        }

        if (self::UPDATE_FLAG === $flag && !$this->FLAG_DONT_UPDATE_ALIAS) {
            $old = new self($this->id);
            //Изменение alias если изменено название, а alias не изменен
            \PartnerApi\Model\AliasChange::aliasChange($this, $old, 'title');
        }
    }

    /**
     * Функция срабатывает перед записью.
     *
     * @param string $flag - флаг какое действие происходит, insert или update
     *
     * @throws \RS\Db\Exception
     * @throws \RS\Exception
     */
    public function afterWrite($flag)
    {
        //Переносим временные объекты, если таковые имелись
        if ($this['_tmpid'] < 0) {

            //Получим id слов, которые прикреплены
            $tags = OrmRequest::make()
                ->from(new TagLink())
                ->where([
                    'type'    => self::TAG_TYPE,
                    'link_id' => $this['_tmpid'],
                ])
                ->exec()->fetchSelected('word_id', 'word_id');

            //Удалим предварительные денные по тегам и слову
            if ($tags) {
                OrmRequest::make()
                    ->from(new TagLink())
                    ->where([
                        'type'    => self::TAG_TYPE,
                        'link_id' => $this['id'],
                    ])
                    ->whereIn('word_id', $tags)
                    ->delete()
                    ->exec();
            }

            OrmRequest::make()
                ->update(new TagLink())
                ->set(['link_id' => $this['id']])
                ->where([
                    'type'    => self::TAG_TYPE,
                    'link_id' => $this['_tmpid'],
                ])->exec();

            OrmRequest::make()
                ->update(new Image())
                ->set(['linkid' => $this['id']])
                ->where([
                    'type'   => self::IMAGES_TYPE,
                    'linkid' => $this['_tmpid'],
                ])->exec();
        }

        $this->updateSearchIndex();
    }

    /**
     * Возвращает список фотографий, привязанных к статье.
     *
     * @return array of \Photo\Model\Orm\Photo
     */
    public function getPhotos()
    {
        $photo_api = new PhotoApi();

        return $photo_api->getLinkedImages($this['id'], self::IMAGES_TYPE);
    }

    /**
     * Возвращает краткий текст заданный пользователем,
     * а если он не задан, то сформированный из основного текста.
     *
     * @param int  $length - длинна текста
     * @param bool $html   - выводить как HTML?
     *
     * @return string
     */
    public function getPreview($length = 500, $html = true)
    {
        $preview = $this['short_content'];
        if (!$html) {
            $preview = strip_tags($preview);
        }
        if (empty($preview)) {
            $text    = preg_replace('/\\<br.*?\\>/iu', "\n", strip_tags($this['content'], '<br>'));
            $preview = nl2br(\RS\Helper\Tools::teaser($text, $length));
        }

        return $preview;
    }

    /**
     * Возвращает объект категории, к которой принадлежит статья.
     *
     * @return Category
     */
    public function getCategory()
    {
        return new Category($this['parent']);
    }

    /**
     * Возвращает url на страницу просмотра новости.
     *
     * @param bool $absolute - вернуть абсолютный url
     *
     * @return string
     */
    public function getUrl($absolute = false)
    {
        $id        = !empty($this['alias']) ? $this['alias'] : $this['id'];
        $dir       = $this->getCategory();
        $dir_alias = $dir['alias'] ?: $this['parent'];

        return \RS\Router\Manager::obj()->getUrl('article-front-view', ['category' => $dir_alias, 'id' => $id], $absolute);
    }

    /**
     * Возвращает URL страницы новости.
     *
     * @param bool $absolute - Если true, то вернет абсолютный URL, иначе относительный
     *
     * @return string
     */
    public function getUrlByAlias($absolute = false)
    {
        return $this->getUrl($absolute);
    }

    /**
     * Удаляет статью.
     *
     * @return bool
     */
    public function delete()
    {
        if (empty($this['id'])) {
            return false;
        }

        //Удаляем фотографии, при удалении товара
        $photo_api = new PhotoApi();
        $photo_api->setFilter('linkid', $this['id']);
        $photo_api->setFilter('type', self::IMAGES_TYPE);
        $photo_list = $photo_api->getList();
        foreach ($photo_list as $photo) {
            // @var \Photo\Model\Orm\Image $photo
            $photo->delete();
        }

        //Удалим тэги
        $tags_api = new \Tags\Model\Api();
        $tags_api->delByLinkAndType($this['id'], self::TAG_TYPE);

        return parent::delete();
    }

    public function getUser()
    {
        return new User($this['user_id']);
    }

    /**
     * Возвращает HTML код для блока "Прикреплённые товары".
     */
    public function getAttachProductsDialog()
    {
        return new \Catalog\Model\ProductDialog('attached_products_arr', true, @(array) $this['attached_products_arr']);
    }

    /**
     * Возвращает товары, рекомендуемые вместе с текущим
     *
     * @param bool $only_available - Если true, то возвращаются только те товары, что есть в наличии
     *
     * @return array of Product
     */
    public function getAttachedProducts($only_available = false)
    {
        if (null === $this->attach_products) {
            $this->attach_products         = [];
            $this['attached_products_arr'] = unserialize($this['attached_products']);
            if (isset($this['attached_products_arr']['product'])) {
                foreach ($this['attached_products_arr']['product'] as $id) {
                    $product = new Product($id);
                    if (isset($product['id']) && (!$only_available || $product['num'] > 0)) {//Если товар существует
                        $this->attach_products[] = new Product($id);
                    }
                }
                if (!empty($this->attach_products)) { //Если товары подгружены
                    $this->fast_mark_attached_products_use = true;
                }
            } else {
                $this->fast_mark_attached_products_use = false;
            }
        }

        return $this->attach_products;
    }

    /**
     * Возвращает true или false проверяю есть ли прикреплённые товары или нет.
     *
     * @return bool
     */
    public function isAttachedProductsUse()
    {
        if (null !== $this->fast_mark_attached_products_use) {
            return $this->fast_mark_attached_products_use;
        }
        $this->getAttachedProducts();

        return $this->fast_mark_attached_products_use;
    }

    /**
     * Возвращает райтинг статьи в процентах от 0 до 100.
     *
     * @return int
     */
    public function getRatingPercent()
    {
        return round($this['rating'] / self::MAX_RATING, 1) * 100;
    }

    /**
     * Возвращает средний балл товара.
     *
     * @return float
     */
    public function getRatingBall()
    {
        return round(self::MAX_RATING * ($this->getRatingPercent() / 100), 2);
    }

    /**
     * Возврщает количество комментариев.
     *
     * @return int
     */
    public function getCommentsNum()
    {
        return (int) $this['comments'];
    }

    /**
     * Возвращает клонированный объект статьи.
     *
     * @throws \RS\Event\Exception
     * @throws \RS\Orm\Exception
     *
     * @return Article
     */
    public function cloneSelf()
    {
        //Получим id слов, которые прикреплены
        $tags = OrmRequest::make()
            ->from(new TagLink())
            ->where([
                'type'    => self::TAG_TYPE,
                'link_id' => $this['id'],
            ])
            ->objects();

        /** @var Article $clone */
        $clone = parent::cloneSelf();

        //Клонируем фото, если нужно
        if ($clone['image']) {
            // @var \RS\Orm\Type\Image
            $clone['image'] = $clone->__image->addFromUrl($clone->__image->getFullPath());
        }
        $clone->setTemporaryId();
        //Привяжем фото
        $images = $this->getPhotos();
        foreach ($images as $image) {
            $image['linkid'] = $clone['id'];
            $image['id']     = null;
            $image->insert();
        }

        //Привяжем теги
        foreach ($tags as $tag) {
            // @var \Tags\Model\Orm\Link $tag
            $tag['id']      = null;
            $tag['link_id'] = $clone['id'];
            $tag->insert();
        }

        unset($clone['alias']);

        return $clone;
    }

    /**
     * Обновляет поисковый индекс
     *
     * @throws \RS\Exception
     *
     * @return bool
     */
    public function updateSearchIndex()
    {
        $config = \RS\Config\Loader::byModule($this);
        if ($config['search_fields']) {
            $title = \in_array('title', $config['search_fields']) ? $this['title'] : '';
            \Search\Model\IndexApi::updateSearch($this, $this['id'], $title, $this->getSearchText());

            return true;
        }

        return false;
    }

    /**
     * Возвращает текст, который попадет в индекс для данной статьи.
     *
     * @throws \RS\Exception
     *
     * @return string
     */
    public function getSearchText()
    {
        $config     = \RS\Config\Loader::byModule($this);
        $index_text = [];
        if (\in_array('short_content', $config['search_fields'])) {
            $index_text[] = $this['short_content'];
        }
        if (\in_array('content', $config['search_fields'])) {
            $index_text[] = $this['content'];
        }
        if (\in_array('meta_keywords', $config['search_fields'])) {
            $index_text[] = $this['meta_keywords'];
        }

        return implode(' ', $index_text);
    }
}

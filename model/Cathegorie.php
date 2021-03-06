<?php
class Cathegorie extends Model
{
     public $table = 't_cathegories';

     #region cathegorie

     /**
      * getListeCathegorie
      * return liste cathegorie or unique cathegorie by id
      * @param  int $id
      * @return void
      */
     public function getListeCathegorie($id = null)
     {

          if (empty($id)) {
               $d = $this->find(array());
          } else {
               $d = $this->find([
                    'conditions' => ['id!' => $id]
               ]);
          }

          foreach ($d as $key => $value) {

               $value->nameParent = $this->findFirst([
                    'conditions' => ['id' => $value->categorie_parent],
                    'fields' => 'name'
               ]);
               if (isset($value->nameParent->name)) {
                    $value->nameParent = $value->nameParent->name;
               }
               $d[$key] = $value;
          }

          return $d;
     }

     /**
      * saveCathegorie
      * Sauvegarder ou mettre ajour des catégories
      * @param  string $name
      * @param  string $description
      * @param  string $file
      * @param  int $id
      * @param  int $categorie_parent
      * @return array|stdClass
      */
     public function saveCathegorie(string $name, string $description, array $file, $id = null, $categorie_parent = "NULL")
     {
          $d = new stdClass();

          /* vérification de la présence du rôle dans la BD */
          $d->info = $this->findFirst([

               'conditions' => ['name' => $name]
          ]);

          if (!empty($d->info) && empty($id)) {
               throw new Exception("Cette categorie existe déja ");
          }

          /* Sauvegarde de l'image dans le serveur */
          if (!empty($file['name'])) {
               $e = new UploadImg();
               if (!empty($d->info)) {

                    if (!empty($d->info->img)) {
                         try {
                              $e->remove($d->info->img);
                         } catch (Exception $e) {
                              throw new Exception($e->getMessage());
                         }
                    }
               }

               try {
                    $e->upload($file, 'img/cathegorie', true);
                    $e->reSize(100, 100, 'img/cathegorie', $e->getImg(), true);
                    $img = $e->getImgRezise();

                    $e->remove($e->getImg());
               } catch (Exception $e) {

                    throw new Exception($e->getMessage());
               }
          } elseif (empty($id)) {

               throw new Exception("Vous devez rajouter une image pour le rôle");
          } elseif (!empty($id) && empty($file['name'])) {

               $img = $d->info->img;
          }


          /* sérialisation des donnée a sauvegarder */

          $tag = [
               'name' => htmlspecialchars($name),
               'description' => htmlspecialchars($description),
               'img' => $img
          ];
          if ($categorie_parent != "NULL") {
               $tag['categorie_parent'] = $categorie_parent;
          }
          if (!empty($id)) {
               $tag['id'] = $id;
          }




          $idTag = $this->save($tag);

          if (empty($id) && !empty($idTag)) {

               $id = $idTag;
          }

          $d = $this->getCategorieById($id);

          return $d;
     }

     /**
      * getCategorieById
      * retourne la catégorie qui correspond a l'id passer en paramètre 
      * @param  int $id
      * @return array|stdClass
      */
     public function getCategorieById(int $id)
     {
          $d = new stdClass();

          $d->info = $this->findFirst([

               'conditions' => ['id' => $id]
          ]);

          if (empty($d)) {

               throw new Exception("La categorie n'éxiste pas ");
          }


          $d->info = $this->getChildrenCategorie($d->info);


          return $d->info;
     }
     /**
      * deleteCathegorie
      * supprime la catégorie qui correspond a l'id passer en paramètre 
      * @param  int $id
      * @return void|array|stdClass
      */
     public function deleteCathegorie($id)
     {
          /* vérification si la Cathegorie existe dans la BD */
          $cathegorie = $this->findFirst([
               'conditions' => ['id' => $id]
          ]);

          if (empty($cathegorie)) {
               throw new Exception("La cathegorie ne peut être supprimé car il n'existe pas");
          }

          /* vérification si la catégorie est utiliser sur un article ou un projet  */
          $isPost = $this->find([
               'conditions' => ['cathegories_id' => $id]
          ], 't_cathegories_has_post');

          if (!empty($isPost)) {
               throw new Exception("La cathegorie que vous voulez supprimer et utilisé actuellement par un ou plusieurs Post vous dever changer le tag de ces post avant de supprimer ce tag");
          }



          $this->delete($id);
          $e = new UploadImg();
          $e->remove($cathegorie->img);
     }
     /**
      * getChildrenCategorie
      * retourne les enfants d'une catégorie
      * @param  mixed $cathegorie
      * @return array|stdClass
      */
     private function getChildrenCategorie($cathegorie)
     {

          $d = $this->find([

               'conditions' => ['categorie_parent' => $cathegorie->id]

          ]);

          $cathegorie->child = $d;

          foreach ($cathegorie->child as $key => $value) {
               $cathegorie->child[$key] = $this->getChildrenCategorie($value);
          }



          return $cathegorie;
     }

     /**
      * clearParentCategorie
      * Retire le parent d'une catégorie 
      * @param  int $id
      * @return bool
      */
     public function clearParentCategorie($id)
     {

          $d = $this->findFirst([
               'conditions' => ['id' => $id]
          ]);

          $d->categorie_parent = null;

          $this->save($d);

          return true;
     }
     #endregion


     #region Tags


     /**
      * getListeTags
      *Renvoie la liste des tag
      * @return array|stdClass
      */
     public function getListeTags()
     {

          $d = $this->find([], 't_tags');

          return $d;
     }

     /**
      * saveTag
      * Sauvegarde ou fait la mise ajour d'un tag existant 
      * @param  string $name
      * @param  string $description
      * @param  array $file
      * @param  int $id
      * @param  mixed $tag_parents
      * @return array|stdClass
      */
     public function saveTag(string $name, string $description, array $file, int $id = null, $tag_parents)
     {
          $d = new stdClass();

          /* vérification de la présence du rôle dans la BD */
          $d->info = $this->findFirst([

               'conditions' => ['name_tag' => $name]
          ], 't_tags');

          if (!empty($d->info) && empty($id)) {

               throw new Exception("Ce tag existe déja");
          }

          /* Sauvegarde de l'image dans le serveur */
          if (!empty($file['name'])) {
               $e = new UploadImg();

               if (!empty($d->info)) {

                    if (!empty($d->info->img)) {

                         try {
                              $e->remove($d->info->img);
                         } catch (Exception $e) {
                              throw new Exception($e->getMessage());
                         }
                    }
               }
               try {

                    $e->upload($file, 'img/tag', true);
                    $e->reSize(100, 100, 'img/tag', $e->getImg(), true);
                    $e->remove($e->getImg());
                    $img = $e->getImgRezise();
               } catch (Exception $e) {
                    throw new Exception($e->getMessage());
               }
          } elseif (empty($id)) {

               throw new Exception("Vous devez rajouter une image pour le rôle");
          } elseif (!empty($id) && empty($file['name'])) {

               $img = $d->info->url_tag;
          }


          /* sérialisation des données a sauvegarder */

          $tag = [
               'name_tag' => htmlspecialchars($name),
               'description_tag' => htmlspecialchars($description),
               'url_tag' => $img
          ];
          if (!empty($id)) {
               $tag['id'] = $id;
          }


          $idTag = $this->save($tag, 't_tags');

          if (empty($id) && !empty($idTag)) {

               $id = $idTag;
          }

          $d = $this->getTagById($id);

          return $d;
     }

     /**
      * getTagById
      * renvoie le tag qui correspond a l'id
      * @param  int $id
      * @return array|stdClass
      */
     public function getTagById(int $id)
     {
          $d = new stdClass();

          $d->info = $this->findFirst([

               'conditions' => ['id' => $id]
          ], 't_tags');

          if (empty($d)) {
               throw new Exception("Le tag n'éxiste pas ");
          }

          return $d->info;
     }
     /**
      * deleteTag
      * Supprime le tag qui correspond a l'id passer en paramètre 
      * @param  int $id
      * @return void
      */
     public function deleteTag($id)
     {
          /* vérification si le tag existe dans la BD */
          $tag = $this->findFirst([
               'conditions' => ['id' => $id]
          ], 't_tags');

          if (empty($tag)) {
               throw new Exception("Le rôle ne peut être supprimé car il n'existe pas");
          }



          /* vérification si le tag est utiliser sur une image  */
          $isPost = $this->find([

               'conditions' => ['fk_tag_id' => $id]

          ], 'tags_has_medias');

          if (!empty($isPost)) {

               throw new Exception("Le tag que vous voulez supprimer et utilise actuellement par un ou plusieurs Image vous dever changer le tag de ces post avant de supprimer ce tag");
          }

          $this->delete($id, 't_tags');

          $e = new UploadImg();
          $e->remove($tag->url_tag);
     }
     #endregion


}

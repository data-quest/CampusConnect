<?php
var_Dump($course->home_institut->name);
var_Dump($course->start_semester->getValue('name'));
var_Dump($course->members[0]['course']['start_semester']['name']);
var_Dump($course->members[0]['vorname']);
var_Dump($course->members[0]['nachname']);
//var_Dump(CourseMember::findEachByUser_id(function($c){return $c->user->getFullname();},$course->members[0]->user->getId()));
//var_Dump($course->members->findBy('status', 'tutor autor')->pluck('vorname nachname status'));
//var_Dump($course->members->sendMessage('toArray'));
/*$st = new StudyArea();
$course->study_areas[] = $st;
$st->parent_id = '439618ae57d8c10dcaabcf7e21bcc1d9';
$st->name = 'Testbereich drölfzehn';
$st->info = '';
$st->type = 0;
unset($course->study_areas[0]);
$course->store();
*/
//$course->study_areas[0]->delete();
//$course->study_areas->refresh();
//$bla = DatafieldEntryModel::findByModel($course);
//var_Dump($bla);
//var_Dump($course->toArrayRecursive(1,true));
var_Dump($course['id']);
var_Dump($course->datafields->findBy('name','test')->val('range_id'));
$course->datafields[0]->content = 'blabluddbb';
//$course->store();
//var_Dump($course->toArrayRecursive());
$news = new StudipNews();
var_Dump($news->courses);
var_Dump($news->institutes);
var_Dump($news->users);
?>